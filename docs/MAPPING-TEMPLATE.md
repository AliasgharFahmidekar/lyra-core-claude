# Section → widget mapping (fill before coding)

| # | Section (selector / heading) | Decision | Widget / structure | Dynamic? | Notes |
|---|------------------------------|----------|--------------------|----------|-------|
| 1 | hero `<header class="hero">` | existing | `lyra-section-heading` + core Image | no | |
| 2 | features grid | existing | `lyra-feature-grid` (4 cols) | no | |
| 3 | pricing toggle | NEW widget | `lyra-pricing-toggle` (repeater + JS) | no | behaviour => custom widget |
| 4 | latest posts | Pro | Loop Grid + `loop-item` template | yes | needs Elementor Pro |

Decisions: `existing` · `existing + new control` · `NEW widget` · `core widget only` (heading/image/button) · `container only` · `Pro`.

Rules: reuse before creating; two similar blocks → one widget with controls; a custom widget only for behaviour
(JS/AJAX/Woo data) or a complex repeated structure core widgets cannot reproduce cleanly.
