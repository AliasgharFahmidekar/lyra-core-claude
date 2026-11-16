#!/usr/bin/env python3
"""Zip the plugin for installation: dist/lyra-core-<version>.zip

The zip has ONE top-level folder (lyra-core/) as WordPress expects.
Usage: python scripts/build_zip.py
"""
import os
import re
import sys
import zipfile

ROOT = os.path.dirname(os.path.dirname(os.path.abspath(__file__)))
PLUGIN = os.path.join(ROOT, "lyra-core")
SKIP_DIRS = {"__pycache__", ".git"}
SKIP_FILES = {".DS_Store", "Thumbs.db"}


def main():
    main_file = os.path.join(PLUGIN, "lyra-core.php")
    head = open(main_file, encoding="utf-8").read(4096)
    m = re.search(r"Version:\s*([\w.\-]+)", head)
    if not m:
        sys.exit("no Version header in lyra-core.php")
    version = m.group(1)
    out_dir = os.path.join(ROOT, "dist")
    os.makedirs(out_dir, exist_ok=True)
    out = os.path.join(out_dir, "lyra-core-%s.zip" % version)
    with zipfile.ZipFile(out, "w", zipfile.ZIP_DEFLATED) as z:
        for d, dirs, files in os.walk(PLUGIN):
            dirs[:] = [x for x in dirs if x not in SKIP_DIRS]
            for f in files:
                if f in SKIP_FILES:
                    continue
                full = os.path.join(d, f)
                z.write(full, os.path.join("lyra-core", os.path.relpath(full, PLUGIN)))
    print("wrote", out)


if __name__ == "__main__":
    main()
