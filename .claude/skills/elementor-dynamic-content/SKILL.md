---
name: elementor-dynamic-content
description: Making Lyra Core pages dynamic with Elementor Pro: dynamic tags (built-in and custom), Loop Grid with loop-item templates, CPT/ACF/meta fields, Theme Builder header/footer/single/archive templates and display conditions, and how to bind them from recipes. Use when a design shows data that should come from posts, custom fields, taxonomies, the site, or WooCommerce rather than static text.
---

# Dynamic content

**Check first:** Elementor Pro installed? (admin screen status). Dynamic tags in core controls and Theme Builder / Loop Grid / popups
are Pro features. Without Pro: build the static structure and tell the user which parts need Pro.

## Make a control dynamic-capable
In the widget: `'dynamic' => array('active' => true)` on TEXT, TEXTAREA, URL, MEDIA, NUMBER... controls. Elementor then shows the
dynamic button, and Pro/core tags in the matching category are offered.

## Bind a tag from a recipe
```php
$b::widget( 'lyra-section-heading', $b::with_dynamic(
  array( 'eyebrow' => 'Fallback' ),
  array( 'title' => $b::dynamic( 'post-title' ) )
) );
```
The tag name is the tag's `get_name()`. Common Pro tags: `post-title`, `post-excerpt`, `post-featured-image`, `post-custom-field`
(settings key `key`), `site-title`, `archive-title`, `author-name`, `current-date-time`. **Verify names and setting keys on the
user's version**: build one binding by hand in the editor, read `__dynamic__` in the saved `_elementor_data`, copy it.

## Custom dynamic tag
Edit `lyra-core/includes/class-lyra-dynamic-tags.php` (lazy-loaded; extends `\Elementor\Core\DynamicTags\Tag`; use `Data_Tag` for
URL/media values). Implement `get_name`, `get_title`, `get_group` (`lyra`), `get_categories` (`TEXT_CATEGORY`, `URL_CATEGORY`,
`IMAGE_CATEGORY`, `NUMBER_CATEGORY`...), `register_controls`, `render`. Register it in `Lyra_Elementor::register_tags()`
(`$tags_manager->register( new Class() )`). Sanitize and escape output. Typical sources: `get_post_meta`, `get_field` (ACF, guard with
`function_exists`), terms, Woo product getters (guard `Lyra_Woo::is_active()`).

## Loop Grid (listing posts/products/CPT) — Pro
1. A `loop-item` template (`kind: template`, `template_type: loop-item`) holds ONE card built from containers/widgets bound to dynamic tags.
2. The page uses the Loop Grid widget with `template_id` = that template's post id, plus query settings (post type, count, order, taxonomy).
   Template ids differ per site, so resolve at build time: look the id up with `Lyra_Page_Builder::find_page('<loop-item recipe slug>')`
   inside the recipe file (recipes are plain PHP). Build the loop-item recipe before the page; control keys: copy from a hand-built sample.
3. Style the card with `.lyra-*` classes added through `css_classes`.

## Theme Builder — Pro
`kind: template` + `template_type` `header` | `footer` | `single-post` | `single-page` | `archive` | `search-results` | `error-404`
and `conditions` such as `include/general`, `include/singular/post`, `include/archive/post_archive` (confirm the exact strings by
saving one by hand and reading `_elementor_conditions`). Pages that must not show the theme header/footer use `template => elementor_canvas`.
Header/footer built by recipes replace the theme's; warn the user and give them the way back (disable in Theme Builder).

## Query-driven custom widgets (no Pro)
If Pro is not available, a custom widget can run its own `WP_Query` with controls for post type / count / taxonomy, render cards, and
cache with transients where useful. Offer editor demo data via `lyra_is_editor()` when the query is empty.

## Checklist before saying "done"
Tag names verified against a hand-built sample · fallbacks set for empty fields · escaped output · works with RTL · Pro-only parts
clearly listed · note that nothing was executed here.
