---
description: Lint the plugin, fix findings and build the install zip
---

Follow `.claude/skills/plugin-qa-and-delivery/SKILL.md`: run `python scripts/lint_plugin.py lyra-core`, fix every error and
warning that is not the "php not installed" notice, grep for leftover placeholders, bump the version if code changed, run
`python scripts/build_zip.py`, and report what was and was not verified.
