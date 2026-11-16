# Workflow: from design to buildable section

1. **Inputs.** Designs in `designs/<name>/`. Ask once only for what is missing (Elementor/Pro/Woo, theme, RTL).
2. **Analyze.** `python scripts/analyze_html.py designs/<name>/index.html` (outline, Tailwind tokens, icons, images, forms).
3. **Map.** Fill `docs/MAPPING-TEMPLATE.md`. Reuse widgets first.
4. **Widgets.** `python scripts/new_widget.py --name <n> --title "<T>"` → controls, render, CSS.
5. **Tokens/CSS.** Convert the design's config to `tokens.css`; add scoped rules to `components.css`.
6. **Recipe.** One file per page/template in `lyra-core/includes/recipes/`.
7. **QA.** `python scripts/lint_plugin.py lyra-core` → `python scripts/build_zip.py`.
8. **Install & verify on a real site.** Elementor → (WooCommerce) → plugin → Lyra Core screen → Build. Send back errors/screenshots.
