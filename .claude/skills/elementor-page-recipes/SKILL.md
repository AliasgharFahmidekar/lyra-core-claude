---
name: elementor-page-recipes
description: How to write Lyra Core page/template recipes (PHP or JSON) that Lyra_Page_Builder turns into Elementor pages: element shape, Flexbox container settings, responsive keys, repeater items, media/icon values, templates and conditions. Use when creating or editing anything in lyra-core/includes/recipes/.
---

# Recipes

A recipe returns an array (PHP) or is a JSON object with the same keys. Start from `lyra-core/includes/recipes/example-landing.php`.

```php
return array(
  'slug' => 'home', 'title' => 'Home', 'kind' => 'page', 'status' => 'draft',
  'template' => 'elementor_header_footer',   // or elementor_canvas / default
  'elements' => array( $b::container( $settings, array( $b::widget( 'lyra-feature-grid', $settings ) ) ) ),
);
```
Helpers on `Lyra_Page_Builder`: `container()`, `widget()`, `item()` (repeater item + `_id`), `dims()`, `gap()`,
`dynamic()`, `with_dynamic()`. Ids and `isInner` are assigned automatically and deterministically.
Keep recipes deterministic: no `rand()`, no dates, no site-specific ids (look up by slug at render time instead).

## Container (Flexbox) keys
| Goal | Key → value |
|---|---|
| Boxed/full | `content_width` → `boxed`\|`full`; `boxed_width` → `{unit:'px',size:1140}` |
| Direction | `flex_direction` → `column`\|`row`\|`column-reverse`\|`row-reverse` |
| Gap | `flex_gap` → `$b::gap(24)` |
| Main / cross axis | `flex_justify_content`, `flex_align_items` |
| Wrap | `flex_wrap` → `wrap` |
| Padding/margin | `padding`, `margin` → `$b::dims(top,right,bottom,left)` (strings inside) |
| Background | `background_background:'classic'`, `background_color:'#fff'` |
| Radius / min height / width | `border_radius` (dims), `min_height` `{unit,size}`, `width` `{unit:'%',size:100}` |
| Class / id / tag | `css_classes`, `_element_id`, `html_tag` (`section`, `header`, `main`) |

Responsive: add `_tablet` / `_mobile` (`flex_direction_mobile`, `padding_mobile`). Prefer the kit's CSS classes for look and
Elementor settings only for structure (direction, gap, alignment, width).

## Widget settings
- Keys are the control ids from the widget's `add_control()`. Unknown keys are ignored silently.
- Repeater items: `$b::item(array(...))`. Media: `{url:'https://...', id:123}`. Icon: `{value:'fas fa-star', library:'fa-solid'}`.
- Core widget keys (heading `title`, image `image`, button `text`/`link`...): read Elementor's source or copy from a
  hand-built page's `_elementor_data`; do not guess.
- Dynamic bindings: see `elementor-dynamic-content`.

## Templates (Theme Builder / saved sections)
`'kind' => 'template', 'template_type' => 'section'|'header'|'footer'|'single-post'|'archive'|'loop-item', 'conditions' => array('include/general')`.
Header/footer/single/archive/loop-item/popup need Elementor Pro; the builder refuses otherwise. Conditions refresh is best effort:
confirm in Templates → Theme Builder on the live site.

## Behaviour of the builder
Idempotent (same slug → same post), refuses to overwrite manually edited posts unless "force", default status `draft` for pages,
`publish` for templates. After writing it clears Elementor's CSS cache. Verify by opening the page with "Edit with Elementor":
the Navigator should list the containers; an empty editor means a data/meta problem (check `_elementor_edit_mode`, JSON slashing).

## Containers availability
Flexbox Containers are default-on from Elementor 3.16 (experimental from 3.6). The admin screen shows the status. If unavailable
and the user cannot enable it (Settings → Features → Flexbox Container), say so rather than silently switching to sections.
