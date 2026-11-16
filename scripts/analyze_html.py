#!/usr/bin/env python3
"""Outline a reference HTML design so it can be mapped to Elementor widgets.

Usage: python analyze_html.py page.html [--depth 3] [--json]

Prints: title/lang/dir, fonts, Tailwind config (colors, fonts, radii...), a
section outline (tag, id, key classes, text snippet, counts), icon usage,
images and form fields. Standard library only.
"""
import argparse
import json
import re
import sys
from html.parser import HTMLParser

VOID = {"area", "base", "br", "col", "embed", "hr", "img", "input", "link", "meta", "source", "track", "wbr"}
SKIP_TEXT = {"script", "style"}


class Node:
    def __init__(self, tag, attrs, parent):
        self.tag, self.attrs, self.parent = tag, dict(attrs), parent
        self.children, self.text = [], []

    @property
    def classes(self):
        return (self.attrs.get("class") or "").split()

    def all_text(self):
        parts = list(self.text)
        for c in self.children:
            if c.tag not in SKIP_TEXT:
                parts.append(c.all_text())
        return re.sub(r"\s+", " ", " ".join(parts)).strip()

    def walk(self):
        yield self
        for c in self.children:
            yield from c.walk()


class Builder(HTMLParser):
    def __init__(self):
        super().__init__(convert_charrefs=True)
        self.root = Node("root", [], None)
        self.cur = self.root
        self.scripts, self.styles, self.links = [], [], []
        self._buf = None

    def handle_starttag(self, tag, attrs):
        n = Node(tag, attrs, self.cur)
        self.cur.children.append(n)
        if tag == "link":
            self.links.append(dict(attrs))
        if tag not in VOID:
            self.cur = n
            if tag in SKIP_TEXT:
                self._buf = (tag, [])

    def handle_endtag(self, tag):
        if tag in VOID:
            return
        if self._buf and self._buf[0] == tag:
            body = "".join(self._buf[1])
            (self.scripts if tag == "script" else self.styles).append(body)
            self._buf = None
        n = self.cur
        while n is not None and n.tag != tag:
            n = n.parent
        if n is not None and n.parent is not None:
            self.cur = n.parent

    def handle_data(self, data):
        if self._buf:
            self._buf[1].append(data)
        elif data.strip():
            self.cur.text.append(data.strip())


def extract_tailwind(scripts):
    for s in scripts:
        m = re.search(r"tailwind\.config\s*=\s*", s)
        if not m:
            continue
        i = s.find("{", m.end())
        if i < 0:
            continue
        depth, j = 0, i
        while j < len(s):
            if s[j] == "{":
                depth += 1
            elif s[j] == "}":
                depth -= 1
                if depth == 0:
                    break
            j += 1
        raw = s[i:j + 1]
        try:  # tolerant JS-object -> JSON
            fixed = re.sub(r"(['\"])?([A-Za-z_][\w-]*)(['\"])?\s*:", r'"\2":', raw)
            fixed = fixed.replace("'", '"')
            fixed = re.sub(r",\s*([}\]])", r"\1", fixed)
            return json.loads(fixed), raw
        except Exception:
            return None, raw
    return None, None


def describe(n, depth, maxd):
    classes = [c for c in n.classes][:10]
    desc = {
        "tag": n.tag,
        "id": n.attrs.get("id"),
        "classes": classes,
        "text": n.all_text()[:70],
        "buttons": sum(1 for x in n.walk() if x.tag == "button"),
        "links": sum(1 for x in n.walk() if x.tag == "a"),
        "images": sum(1 for x in n.walk() if x.tag == "img"),
        "inputs": sum(1 for x in n.walk() if x.tag in ("input", "select", "textarea")),
    }
    for k, v in n.attrs.items():
        if k.startswith("data-") or k in ("aria-label", "role"):
            desc.setdefault("attrs", {})[k] = v
    kids = [c for c in n.children if c.tag not in SKIP_TEXT and c.tag not in VOID | {"link", "meta"}]
    if depth < maxd and kids:
        desc["children"] = [describe(c, depth + 1, maxd) for c in kids]
    return desc


def print_outline(d, indent=0):
    cls = ".".join(d["classes"][:5])
    head = f'{"  " * indent}<{d["tag"]}'
    if d["id"]:
        head += f'#{d["id"]}'
    if cls:
        head += f".{cls}"
    head += ">"
    meta = []
    for k in ("buttons", "links", "images", "inputs"):
        if d[k]:
            meta.append(f"{k}={d[k]}")
    if d["text"]:
        meta.append(f'"{d["text"]}"')
    print(head + ("  " + "  ".join(meta) if meta else ""))
    for c in d.get("children", []):
        print_outline(c, indent + 1)


def main():
    ap = argparse.ArgumentParser()
    ap.add_argument("html")
    ap.add_argument("--depth", type=int, default=3)
    ap.add_argument("--json", action="store_true")
    a = ap.parse_args()

    src = open(a.html, encoding="utf-8", errors="replace").read()
    b = Builder()
    b.feed(src)
    nodes = list(b.root.walk())
    html = next((n for n in nodes if n.tag == "html"), None)
    body = next((n for n in nodes if n.tag == "body"), b.root)
    title = next((n.all_text() for n in nodes if n.tag == "title"), "")

    tw, tw_raw = extract_tailwind(b.scripts)
    fonts = [l.get("href") for l in b.links if "font" in (l.get("href") or "")]
    icons = {}
    for n in nodes:
        cl = n.classes
        if "material-symbols-outlined" in cl or "material-icons" in cl:
            icons[n.all_text()] = icons.get(n.all_text(), 0) + 1
        for c in cl:
            if c.startswith(("fa-", "bi-", "ti-")) or c in ("fa", "fas", "far", "fab"):
                icons[c] = icons.get(c, 0) + 1
    imgs = [{"src": (n.attrs.get("src") or "")[:90], "alt": n.attrs.get("alt", "")} for n in nodes if n.tag == "img"]
    fields = [
        {"tag": n.tag, "type": n.attrs.get("type"), "name": n.attrs.get("name") or n.attrs.get("id"),
         "placeholder": n.attrs.get("placeholder")}
        for n in nodes if n.tag in ("input", "select", "textarea")
    ]
    used_classes = sorted({c for n in nodes for c in n.classes})

    out = {
        "title": title,
        "lang": html.attrs.get("lang") if html else None,
        "dir": (html.attrs.get("dir") if html else None) or body.attrs.get("dir"),
        "fonts": fonts,
        "tailwind_config": tw if tw is not None else ("unparsed: see raw" if tw_raw else None),
        "tailwind_raw": None if tw is not None else tw_raw,
        "icons": icons,
        "images": imgs,
        "form_fields": fields,
        "class_count": len(used_classes),
        "outline": describe(body, 0, a.depth),
    }
    if a.json:
        print(json.dumps(out, ensure_ascii=False, indent=2))
        return

    print(f"# {a.html}")
    print(f"title: {title!r}  lang: {out['lang']}  dir: {out['dir']}")
    print(f"fonts: {fonts}")
    print("\n## Tailwind config")
    if tw:
        print(json.dumps(tw, ensure_ascii=False, indent=2)[:4000])
    elif tw_raw:
        print(tw_raw[:4000])
    else:
        print("(none found)")
    print(f"\n## Icons ({len(icons)} distinct): {dict(list(icons.items())[:40])}")
    print(f"## Images ({len(imgs)}): {imgs[:10]}")
    print(f"## Form fields ({len(fields)}): {fields[:20]}")
    print(f"## Distinct classes: {len(used_classes)}")
    print("\n## Outline")
    print_outline(out["outline"])


if __name__ == "__main__":
    sys.exit(main())
