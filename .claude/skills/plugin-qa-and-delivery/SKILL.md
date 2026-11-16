---
name: plugin-qa-and-delivery
description: Static QA, versioning and packaging of the Lyra Core plugin (lint script, PHP 7.4 rules, security checks, zip) and how to report honestly what was and was not verified. Use before any delivery, after code changes, or when an Elementor/PHP fatal error is reported.
---

# QA and delivery

## Checklist
1. `python scripts/lint_plugin.py lyra-core` — must report 0 errors. The only acceptable warning is "php is not installed" (and say so).
   It checks: `<?php` at byte 0 (no BOM), brace balance, Elementor-extending file required at load time, manifest ↔ files, PHP-8-only
   syntax vs `Requires PHP`, missing `index.php`, text-domain mismatch, and runs `php -l` when PHP exists.
2. `grep -rn "xx_\|XX_\|TODO\|FIXME" lyra-core` — no leftovers from templates.
3. Manual review: every output escaped; nonce + `current_user_can` on admin handlers; sanitized input; no inline `<script>`; text in `__()`
   with `lyra-core`; widget names/ids unchanged for existing controls.
4. If PHP/code changed: bump `Version:` in `lyra-core.php` **and** `LYRA_VERSION` (keep identical).
5. `python scripts/build_zip.py` → `dist/lyra-core-<version>.zip` (single top-level folder `lyra-core/`).
6. Final message: what changed, what was statically checked, what must be tested live, install order
   (Elementor → WooCommerce if used → plugin → Lyra Core screen → Build), and how to get debug info.

## Debug info to ask for
`WP_DEBUG` + `WP_DEBUG_LOG` true in wp-config.php, then send `wp-content/debug.log`; the Lyra Core screen log; Elementor/Pro/Woo/PHP versions
(shown in the status table); a screenshot of the editor or front end.

## Known failure patterns
- `Class "Elementor\Widget_Base" not found` on activation → an Elementor-extending file is required from the main file; move the require into a hook (the lint script flags it).
- Widget missing in the panel → not in the manifest, class name mismatch, or fatal in its file (check `debug.log` and the Lyra log).
- Settings from a recipe do nothing → wrong control keys (unknown keys are ignored silently).
- Empty page in the editor → `_elementor_edit_mode`/`_elementor_data` slashing, or Containers feature disabled.
- Styles not loading → handles not listed in `get_style_depends()`, or cached CSS (Elementor → Tools → Regenerate CSS).

## Never say
"pixel-perfect", "tested", "works" — unless it ran on a live site. Say "statically verified" and list the live checks.
