---
name: design-tokens-and-css
description: Converting a reference design's styling (Tailwind config, Figma tokens, fonts, spacing) into Lyra Core's tokens.css and scoped, RTL-safe, mobile-first component CSS without the Tailwind CDN. Use when matching a design's look, setting up colours/fonts, or fixing visual differences.
---

# Tokens and CSS

1. **Extract.** `python scripts/analyze_html.py <file>` prints the Tailwind config (colors, fonts, radii, spacing), icons, images, fonts.
   If the config is "unparsed", read the raw block it prints.
2. **tokens.css.** Map every colour/font/radius/shadow/spacing to `--lyra-*` variables (keep the existing names; add new ones, e.g.
   `--lyra-accent`). Optionally mirror them to Elementor global colours/fonts in a recipe's `page_settings` or in Site Settings.
3. **Fonts.** Bundle `.woff2` in `lyra-core/assets/fonts/`, `@font-face` in `tokens.css` with relative URLs and `font-display: swap`.
   No Google Fonts requests (privacy, speed, offline). Persian projects: bundle Vazirmatn or the design's font.
4. **components.css.** Translate only the utility classes the design actually uses into rules scoped under `.lyra-<widget>__part`.
   Never style bare tags; never ship the Tailwind CDN. Use logical properties (`margin-inline`, `padding-block`, `text-align: start`,
   `inset-inline-start`) so RTL needs no second sheet. Mobile-first, then `@media (min-width: 768px)` / `1025px` (match Elementor's
   breakpoints: tablet ≤1024, mobile ≤767) unless the design uses other values.
5. **Icons.** Material Symbols / Font Awesome in the design → Elementor ICONS controls (Font Awesome available out of the box) or
   bundled SVGs (`Lyra_Icons`). Do not load icon fonts from a CDN.
6. **Images.** Placeholders in the design become MEDIA controls with an empty default and a neutral CSS placeholder; do not hotlink design images.
7. **Compare.** Section by section against the HTML/screenshot: spacing, font sizes, weights, colours, radii, shadows, breakpoints,
   hover/focus states, RTL mirroring (arrows/chevrons flip, icons with direction use `transform: scaleX(-1)` under `[dir=rtl]`).
8. **Elementor overrides.** Theme/Elementor styles can leak in: raise specificity with the widget wrapper (`.elementor-widget-lyra-xxx .lyra-xxx__part`)
   instead of `!important`.

Say what you compared and what still needs eyes on the live site; do not claim pixel-perfect.
