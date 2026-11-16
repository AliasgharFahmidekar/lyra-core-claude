# Lyra Core — agent instructions

Generic (non-branded) base for building **dynamic, editable Elementor / Elementor Pro pages from code**.
You get reference designs (HTML / Tailwind / Figma export) and must produce the matching section(s) or page(s) as
Elementor containers + widgets, buildable with one click from wp-admin. Reply to the user in the language they write in.

## Layout

```
lyra-core/                  the WordPress plugin (zip this folder)
  lyra-core.php             header + constants + NON-Elementor requires only
  includes/                 helpers, page builder, settings screen, Elementor integration
    class-lyra-page-builder.php   recipes -> pages / Theme Builder templates (idempotent)
    class-lyra-elementor.php      category, widget MANIFEST, dynamic tag registration
    class-lyra-base-widget.php    base class for widgets (lazy-loaded)
    class-lyra-dynamic-tags.php   dynamic tags (lazy-loaded)
    recipes/                      one file per page/template (PHP array or JSON)
  widgets/<group>/class-lyra-*.php
  assets/{css,js,fonts}     tokens.css, components.css, frontend.js
designs/<name>/             reference designs dropped in by the user (index.html, screenshot, notes.md)
scripts/                    analyze_html.py, new_widget.py, lint_plugin.py, build_zip.py
docs/                       ARCHITECTURE.md, WORKFLOW.md, MAPPING-TEMPLATE.md
.claude/skills/             how-to skills (read the matching one before acting)
.claude/commands/           /build-section, /new-widget, /qa
```

## When the user hands you a design

Follow `.claude/skills/html-to-elementor-section/SKILL.md`. In short:
1. `python scripts/analyze_html.py designs/<name>/index.html` and read the output + the HTML.
2. Write the section → widget mapping table (`docs/MAPPING-TEMPLATE.md`). Reuse existing widgets first.
3. Missing widgets: `python scripts/new_widget.py --name x --title "X"`, then fill controls/render/CSS.
4. Tokens + scoped CSS from the design (never the Tailwind CDN).
5. Add a recipe in `lyra-core/includes/recipes/`.
6. `python scripts/lint_plugin.py lyra-core` → fix everything → `python scripts/build_zip.py`.

Skills: `html-to-elementor-section` (main flow), `elementor-widget-builder`, `elementor-page-recipes`,
`elementor-dynamic-content` (dynamic tags, Loop, Theme Builder, CPT/ACF), `woocommerce-elementor-pages`,
`design-tokens-and-css`, `plugin-qa-and-delivery`.

## Hard rules (each one prevents a known real failure)

- **Never extend an Elementor class at load time.** `extends \Elementor\...` lives only in files that are `require`d
  from inside Elementor hooks (widgets via the manifest, `class-lyra-base-widget.php`, `class-lyra-dynamic-tags.php`).
  The main file may only require files that do not depend on Elementor. `lint_plugin.py` checks this.
- PHP 7.4 syntax (no `match`, `?->`, union types, constructor promotion, `readonly`, attributes).
- Every text, icon, image, link, colour and repeating item is a **control**, default = the design's text.
  Do not hard-code content in `render()`.
- Escape all output (`esc_html`, `esc_attr`, `esc_url`, `wp_kses_post`); nonce + capability on every admin/AJAX handler.
- One prefix: classes `Lyra_*`, widgets `lyra-*`, CSS `.lyra-*`, text domain `lyra-core`, options `lyra_*`.
- CSS: scoped under `.lyra-*`, variables from `tokens.css`, logical properties (RTL-safe), mobile-first, no CDN, local fonts.
- Never guess Elementor control keys: read the widget source, or build the thing once by hand in the editor and copy the
  keys from its `_elementor_data`. Unknown keys are ignored silently ("nothing happens").
- Recipes must be deterministic (no random ids/values) so rebuild and the "edited manually" check work.
- WooCommerce stays the owner of cart, checkout and orders; widgets are views. Guard every Woo/Pro call.
- Elementor Pro features (Theme Builder, Loop Grid, dynamic tags beyond core) only when Pro is confirmed; otherwise build body content.

## Be honest about verification

The sandbox usually has no PHP, WordPress or Elementor. Statically verified (lint) is not tested. Never say
"pixel-perfect" or "works". State what was checked and what must be confirmed on the live site; ask for the error text /
`wp-content/debug.log` / screenshots and iterate.
