---
name: elementor-widget-builder
description: How to create or extend a Lyra Core Elementor widget (controls, repeaters, responsive controls, dynamic support, render, CSS/JS, manifest). Use when a design section needs a custom widget, when adding controls to an existing widget, or when asked to scaffold a widget.
---

# Building a widget

## Do I need a custom widget?
No if container + core Heading/Image/Button/Icon List reproduces it. Yes for behaviour (tabs, toggle, counter, AJAX, Woo data),
or a complex repeated structure. If two blocks differ slightly, one widget with controls beats two widgets.

## Steps
1. `python scripts/new_widget.py --name pricing-toggle --title "Pricing Toggle" [--group pricing]`
   → creates `lyra-core/widgets/<group>/class-lyra-pricing-toggle.php` and one manifest line.
2. Open the file and `lyra-core/widgets/general/class-lyra-feature-grid.php` (repeater + responsive control reference)
   and `class-lyra-section-heading.php` (text/URL/switcher/choose reference).
3. **Controls.** Every text, icon, image, link, colour, repeating item is a control; default = design text.
   - Text/Textarea/URL/Media/Icons controls that make sense for CPT/ACF data: `'dynamic' => array('active' => true)`.
   - Repeaters: `new \Elementor\Repeater()`, `title_field`, sensible defaults.
   - Optional parts: SWITCHER with `return_value => 'yes'` + `condition`.
   - Responsive values: `add_responsive_control` with `selectors` (columns, gap, alignment).
   - Style tab: use `$this->add_text_style($id, $label, $selector)` from the base class; add more groups
     (background, border, box shadow, spacing) with Elementor group controls.
4. **render().** Read with `$this->s('key', default)` and `$this->field($item, 'key')`. Wrapper
   `<div class="lyra-widget lyra-<name>">`. Escape everything (`esc_html`, `esc_url`, `wp_kses_post`). Links through
   `$this->add_link_attributes()`. Icons through `lyra_render_icon($icon, 'fallback-name')`.
5. **Editor demo data.** When real data can be empty (Woo cart, query results) and `lyra_is_editor()` is true,
   render fixed sample data so the widget is designable. Never on the live front end.
6. **CSS** → append scoped rules to `assets/css/components.css` (`.lyra-<name>__part`), variables from tokens, logical properties.
   **JS** → `assets/js/frontend.js`, event delegation, no inline scripts. Per-widget assets: register extra handles in
   `Lyra_Assets::register()` and list them in `get_style_depends()` / `get_script_depends()`.
7. Lint: `python scripts/lint_plugin.py lyra-core`.

## Rules
- Concrete widgets implement `register_controls()` (the base class shims the old `_register_controls`).
- Never `require` a widget or the base class from the main file; only the manifest loads them.
- PHP 7.4 only. One text domain `lyra-core`. Strings in `__()`.
- Keep control ids stable once recipes use them; renaming breaks existing pages (add an alias/migration instead).
