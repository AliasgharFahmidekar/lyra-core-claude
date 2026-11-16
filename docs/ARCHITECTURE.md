# Architecture

**Load order.** `lyra-core.php` requires only Elementor-independent files, then `plugins_loaded` (priority 20) →
`Lyra_Plugin::boot()`. If `elementor/loaded` has fired it creates `Lyra_Elementor`, which hooks
`elementor/widgets/register` (or the legacy hook below Elementor 3.5) and the dynamic-tags hook. Inside those hooks the
base widget, manifest widgets and tag classes are `require`d. Activation only sets default options.

**Page builder.** `Lyra_Page_Builder` loads recipes (PHP arrays or JSON), assigns deterministic ids, writes
`_elementor_data` with `wp_slash`, stores the recipe slug and a content hash. Rebuilding updates the same post;
manually edited posts are not overwritten unless "force" is ticked. `kind: template` creates `elementor_library`
posts (Theme Builder header/footer/single/archive/loop-item need Elementor Pro) with optional display `conditions`.

**Widgets.** `Lyra_Elementor::$manifest` maps class → file. Add one with `scripts/new_widget.py`. The filter
`lyra_widget_manifest` lets another plugin/theme add widgets; `lyra_recipe_dirs` adds recipe folders.

**Dynamic content.** Controls opt in with `'dynamic' => array('active' => true)`. Recipes bind tags with
`Lyra_Page_Builder::with_dynamic()` + `::dynamic('post-title')`. Custom tags live in `class-lyra-dynamic-tags.php`.

**Assets.** `tokens.css` (variables) → `components.css` (scoped) → `frontend.js` (event delegation). Widgets declare them in
`get_style_depends()` / `get_script_depends()` so they load only where used.
