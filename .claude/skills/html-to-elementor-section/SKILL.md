---
name: html-to-elementor-section
description: Main workflow for turning reference designs (HTML/Tailwind/Figma export in designs/) into one or more editable Elementor sections or pages built by the Lyra Core plugin. Use whenever the user hands over a design and asks to build a section, a page, or several, or says things like "طرح رو به المنتور تبدیل کن", "این سکشن رو بساز", "build this section from the design".
---

# Design → buildable Elementor section(s)

Read `CLAUDE.md` first (hard rules). This skill is the orchestrator; it calls the other skills.

## Steps

1. **Inventory inputs.** `ls designs/`. Ask once, only if unknown: Elementor version, Pro yes/no, Woo yes/no, theme, RTL.
   Pro decides whether Theme Builder (header/footer/single/archive/loop) and Loop Grid can be used.
2. **Analyze each HTML.** `python scripts/analyze_html.py designs/<name>/index.html` (add `--depth 4` for detail).
   Read the output, then open the HTML for what the script cannot see (hover states, JS, responsive classes).
   If a screenshot exists, compare against it at the end.
3. **Map sections → widgets.** Fill the table from `docs/MAPPING-TEMPLATE.md`. Reuse existing widgets in
   `lyra-core/widgets/` first; core Elementor widgets (heading, image, button, icon-list) for plain things; a custom
   widget only for behaviour or complex repeated structure. Show the table to the user before coding when more than
   ~5 new widgets are needed.
4. **Tokens and CSS** → `design-tokens-and-css` skill.
5. **Widgets** → `elementor-widget-builder` skill (`scripts/new_widget.py`).
6. **Dynamic parts** (posts, CPT/ACF fields, product data, Theme Builder) → `elementor-dynamic-content` skill.
   Woo cart/checkout/thank-you → `woocommerce-elementor-pages` skill.
7. **Recipe** → `elementor-page-recipes` skill. One recipe per page. For "just this section" requests either:
   - a page recipe containing only that section (user copies it into an existing page via Elementor), or
   - `kind: template, template_type: section` so it appears under Templates and can be inserted anywhere.
8. **QA and delivery** → `plugin-qa-and-delivery` skill.

## Output expectations

- The user can click Lyra Core → Build and get editable Elementor pages that visually follow the design.
- Final message: what was built, mapping table, what is new/reused, what is only statically checked, how to install
  and test, and what to send back if something fails (error text, `wp-content/debug.log`, screenshot).

## Common mistakes

- Creating a widget for something a container + heading + image already does.
- Hard-coding text/icons/images in `render()` instead of controls.
- Shipping Tailwind CDN or Google Fonts requests.
- Guessing control keys for core/Pro widgets instead of reading source or a hand-built sample.
- Claiming "pixel-perfect" or "works" without a live test.
