#!/usr/bin/env python3
"""Static checks for a WordPress/Elementor plugin directory (no PHP needed).

Usage: python lint_plugin.py <plugin-dir> [--strict]

Errors (exit code 1):
  - file does not start with '<?php' (BOM / leading whitespace)
  - unbalanced braces/parentheses (PHP code only, strings/comments ignored)
  - main-file top-level require of a file that extends an Elementor class
    (fatal "Class not found" when Elementor is inactive/late)
  - manifest entry pointing to a missing file or a file without that class
  - PHP-8-only syntax while the header says Requires PHP < 8
  - real `php -l` failure when php is installed
Warnings: missing index.php, closing '?>' at EOF, str_contains family, missing text domain.
"""
import argparse
import os
import re
import shutil
import subprocess
import sys


def php_code_only(src):
    """Return PHP code with strings/comments blanked; HTML outside tags dropped."""
    out, i, n = [], 0, len(src)
    in_php = False
    while i < n:
        if not in_php:
            m = re.search(r"<\?(php|=)?", src[i:])
            if not m:
                break
            i += m.end()
            in_php = True
            continue
        c = src[i]
        two = src[i:i + 2]
        if two == "?>":
            in_php = False
            out.append(";")
            i += 2
        elif two == "//" or c == "#" and src[i:i + 2] != "#[":
            j = i
            while j < n and src[j] != "\n" and src[j:j + 2] != "?>":
                j += 1
            i = j
        elif two == "/*":
            j = src.find("*/", i + 2)
            i = n if j < 0 else j + 2
        elif c in ("'", '"'):
            q, j = c, i + 1
            while j < n and src[j] != q:
                j += 2 if src[j] == "\\" else 1
            out.append(q + q)
            i = j + 1
        elif src[i:i + 3] == "<<<":
            m = re.match(r"<<<\s*['\"]?(\w+)['\"]?\r?\n", src[i:])
            if m:
                end = re.search(r"\n\s*" + m.group(1) + r"\b", src[i + m.end():])
                i = i + m.end() + (end.end() if end else n)
                out.append("''")
            else:
                out.append(c)
                i += 1
        else:
            out.append(c)
            i += 1
    return "".join(out)


def read(p):
    with open(p, "rb") as f:
        return f.read()


def main():
    ap = argparse.ArgumentParser()
    ap.add_argument("plugin_dir")
    ap.add_argument("--strict", action="store_true", help="treat warnings as errors")
    a = ap.parse_args()
    root = os.path.abspath(a.plugin_dir)
    errors, warns = [], []

    php_files = []
    for d, _, fs in os.walk(root):
        for f in fs:
            if f.endswith(".php"):
                php_files.append(os.path.join(d, f))
    rel = lambda p: os.path.relpath(p, root)

    mains = [p for p in php_files if os.path.dirname(p) == root and
             re.search(rb"Plugin Name:", read(p)[:4096])]
    main_file = mains[0] if mains else None
    if not main_file:
        errors.append("no main plugin file with a 'Plugin Name:' header in the plugin root")

    header = read(main_file).decode("utf-8", "replace") if main_file else ""
    m = re.search(r"Requires PHP:\s*([\d.]+)", header)
    req_php = tuple(int(x) for x in m.group(1).split(".")[:2]) if m else (7, 4)
    m = re.search(r"Requires at least:\s*([\d.]+)", header)
    req_wp = tuple(int(x) for x in m.group(1).split(".")[:2]) if m else (5, 8)
    domain = (re.search(r"Text Domain:\s*(\S+)", header) or [None, None])[1]

    # Elementor-extending files
    elementor_ext = set()
    code_cache = {}
    for p in php_files:
        raw = read(p)
        if not raw.startswith(b"<?php"):
            errors.append(f"{rel(p)}: must start with '<?php' (BOM or leading whitespace: {raw[:6]!r})")
        text = raw.decode("utf-8", "replace")
        code = php_code_only(text)
        code_cache[p] = code
        for o, c in (("{", "}"), ("(", ")"), ("[", "]")):
            if code.count(o) != code.count(c):
                errors.append(f"{rel(p)}: unbalanced {o}{c} ({code.count(o)} vs {code.count(c)})")
        if re.search(r"\?>\s*\Z", text):
            warns.append(f"{rel(p)}: closing '?>' at end of file (risk of stray output)")

        uses = set(re.findall(r"^use\s+\\?Elementor\\[\w\\]*?(\w+)\s*;", text, re.M))
        for cm in re.finditer(r"\bclass\s+\w+\s+extends\s+(\\?[\w\\]+)", code):
            parent = cm.group(1)
            base = parent.split("\\")[-1]
            if parent.lstrip("\\").startswith("Elementor\\") or base in uses:
                elementor_ext.add(p)

        if req_php < (8, 0):
            for rx, label in (
                (r"(?<![\$\w>:])match\s*\(", "match expression"),
                (r"\?->", "nullsafe operator"),
                (r"function\s+\w*\s*\([^)]*\)\s*:\s*\??[\w\\]+\s*\|\s*[\w\\]+", "union return type"),
                (r"\(\s*[^)]*\b(?:int|string|array|float|bool)\s*\|\s*(?:int|string|array|float|bool|null)\s+\$", "union param type"),
                (r"__construct\s*\(\s*(?:public|private|protected)\s", "constructor promotion"),
                (r"\breadonly\s+(?:public|private|protected|\$)", "readonly property"),
                (r"#\[", "attribute"),
            ):
                if re.search(rx, code):
                    errors.append(f"{rel(p)}: PHP 8-only syntax ({label}) but header says Requires PHP {'.'.join(map(str, req_php))}")
        if req_wp < (5, 9) and re.search(r"\bstr_(?:contains|starts_with|ends_with)\s*\(", code):
            warns.append(f"{rel(p)}: str_contains/starts_with/ends_with need PHP 8 or WP >= 5.9 polyfill")

    # top-level requires in the main file
    if main_file:
        mcode = code_cache[main_file]
        mtext = read(main_file).decode("utf-8", "replace")
        depth, top = 0, []
        for line in mtext.splitlines():
            stripped = re.sub(r"//.*$", "", line)
            if depth == 0:
                rm = re.search(r"require(?:_once)?\s*\(?\s*[A-Z_]*PATH\s*\.\s*'([^']+)'", stripped)
                if rm:
                    top.append(rm.group(1))
            depth += stripped.count("{") - stripped.count("}")
        for path in top:
            full = os.path.join(root, path)
            if full in elementor_ext:
                errors.append(
                    f"{path}: extends an Elementor class but is required at load time from the main file. "
                    "Load it lazily after 'elementor/loaded' (inside boot()/widget registration), "
                    "otherwise activation fatals with 'Class not found' when Elementor is inactive."
                )
            if not os.path.exists(full):
                errors.append(f"{path}: required by the main file but missing")

    # manifest check
    for p in php_files:
        text = read(p).decode("utf-8", "replace")
        if "manifest" not in text.lower():
            continue
        for cm in re.finditer(r"'([A-Z][A-Za-z0-9_]+)'\s*=>\s*'([\w\-/]+\.php)'", text):
            cls, path = cm.groups()
            full = os.path.join(root, "widgets", path)
            if not os.path.exists(full):
                errors.append(f"{rel(p)}: manifest {cls} -> widgets/{path} does not exist")
            elif not re.search(r"\bclass\s+" + re.escape(cls) + r"\b", read(full).decode("utf-8", "replace")):
                errors.append(f"widgets/{path}: does not declare class {cls}")

    # index.php silence files
    for d, subs, fs in os.walk(root):
        if d != root and not any(x.startswith(".") for x in d.split(os.sep)) and "index.php" not in fs and any(f.endswith(".php") for f in fs):
            warns.append(f"{os.path.relpath(d, root)}/: missing index.php")

    if domain:
        bad = set()
        for p in php_files:
            for dm in re.finditer(r"__\(\s*'[^']*'\s*,\s*'([\w-]+)'\s*\)", read(p).decode("utf-8", "replace")):
                if dm.group(1) != domain:
                    bad.add((rel(p), dm.group(1)))
        for f, d in sorted(bad)[:10]:
            warns.append(f"{f}: text domain '{d}' differs from header '{domain}'")

    # real php -l when available
    php = shutil.which("php")
    if php:
        for p in php_files:
            r = subprocess.run([php, "-l", p], capture_output=True, text=True)
            if r.returncode != 0:
                errors.append(f"{rel(p)}: php -l failed: {(r.stdout + r.stderr).strip()}")
    else:
        warns.append("php is not installed here: syntax was NOT compiled; only static checks ran")

    for w in warns:
        print("WARN ", w)
    for e in errors:
        print("ERROR", e)
    print(f"\n{len(php_files)} php files, {len(errors)} error(s), {len(warns)} warning(s)")
    if errors or (a.strict and warns):
        sys.exit(1)


if __name__ == "__main__":
    main()
