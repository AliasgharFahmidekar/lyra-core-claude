<?php
/**
 * Page builder: turns "recipes" into real, editable Elementor pages/templates.
 *
 * This file does NOT extend any Elementor class, so it is safe to require
 * from the main plugin file.
 *
 * A recipe is a PHP file (returns an array) or a JSON file in
 * includes/recipes/ (extra folders via the `lyra_recipe_dirs` filter):
 *
 *   return array(
 *       'slug'     => 'home',                    // unique, [a-z0-9_-]
 *       'title'    => 'Home',
 *       'kind'     => 'page',                    // page | template
 *       'status'   => 'draft',                   // draft | publish
 *       'template' => 'elementor_header_footer', // pages only
 *       'elements' => array(
 *           Lyra_Page_Builder::container( array( 'flex_direction' => 'column' ), array(
 *               Lyra_Page_Builder::widget( 'lyra-section-heading', array( 'title' => '...' ) ),
 *           ) ),
 *       ),
 *   );
 *
 * Theme Builder / saved templates (kind = template):
 *   'kind' => 'template', 'template_type' => 'header|footer|single-post|archive|loop-item|section|...',
 *   'conditions' => array( 'include/general' )   // Elementor Pro display conditions
 *
 * Optional: 'woo_page' => 'cart|checkout', 'page_slug' => 'real-url-slug',
 *           'page_settings' => array( ... ) saved to _elementor_page_settings.
 *
 * @package LyraCore
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class Lyra_Page_Builder {

	const META_RECIPE = '_lyra_recipe';
	const META_HASH   = '_lyra_data_hash';

	/**
	 * Template types that only work with Elementor Pro.
	 */
	const PRO_TYPES = array( 'header', 'footer', 'single', 'single-post', 'single-page', 'archive', 'search-results', 'error-404', 'loop-item', 'popup' );

	private static $counter = 0;

	/**
	 * Deterministic 7-char id so rebuilds produce identical data.
	 *
	 * @param string $seed Seed string.
	 * @return string
	 */
	private static function make_id( $seed ) {
		return substr( md5( $seed ), 0, 7 );
	}

	/**
	 * Build a container element.
	 *
	 * @param array $settings Container settings (see .claude/skills/elementor-page-recipes).
	 * @param array $children Child elements (containers / widgets).
	 * @return array
	 */
	public static function container( array $settings = array(), array $children = array() ) {
		return array(
			'id'       => '',
			'elType'   => 'container',
			'isInner'  => false,
			'settings' => $settings,
			'elements' => $children,
		);
	}

	/**
	 * Build a widget element.
	 *
	 * @param string $type     Widget name (get_name()).
	 * @param array  $settings Widget settings keyed by control id.
	 * @return array
	 */
	public static function widget( $type, array $settings = array() ) {
		return array(
			'id'         => '',
			'elType'     => 'widget',
			'widgetType' => $type,
			'settings'   => $settings,
			'elements'   => array(),
		);
	}

	/**
	 * Build a repeater item (adds the required _id).
	 *
	 * @param array $fields Item fields.
	 * @return array
	 */
	public static function item( array $fields ) {
		self::$counter++;
		$fields['_id'] = self::make_id( 'item' . self::$counter . wp_json_encode( $fields ) );
		return $fields;
	}

	/**
	 * Dynamic tag shortcode for a control, e.g.
	 *   self::with_dynamic( $settings, array( 'title' => self::dynamic( 'post-title' ) ) )
	 *
	 * @param string $tag      Dynamic tag name (get_name() of the tag).
	 * @param array  $settings Tag settings.
	 * @return string
	 */
	public static function dynamic( $tag, array $settings = array() ) {
		$json = wp_json_encode( (object) $settings );
		return sprintf(
			'[elementor-tag id="%1$s" name="%2$s" settings="%3$s"]',
			substr( md5( $tag . $json ), 0, 7 ),
			$tag,
			rawurlencode( $json )
		);
	}

	/**
	 * Add dynamic bindings to a settings array.
	 *
	 * @param array $settings Widget/container settings.
	 * @param array $dynamic  control id => dynamic() string.
	 * @return array
	 */
	public static function with_dynamic( array $settings, array $dynamic ) {
		$settings['__dynamic__'] = $dynamic;
		return $settings;
	}

	/**
	 * Assign ids and isInner flags recursively.
	 *
	 * @param array  $elements Elements.
	 * @param string $prefix   Id seed prefix (page slug + path).
	 * @param bool   $inner    Whether these are nested containers.
	 * @return array
	 */
	public static function normalize( array $elements, $prefix = '', $inner = false ) {
		$out = array();
		foreach ( array_values( $elements ) as $i => $el ) {
			$path     = $prefix . '/' . $i;
			$el['id'] = self::make_id( $path . ( isset( $el['widgetType'] ) ? $el['widgetType'] : 'c' ) );
			if ( isset( $el['elType'] ) && 'container' === $el['elType'] ) {
				$el['isInner'] = (bool) $inner;
			}
			if ( ! isset( $el['settings'] ) || ! is_array( $el['settings'] ) ) {
				$el['settings'] = array();
			}
			$children       = ( isset( $el['elements'] ) && is_array( $el['elements'] ) ) ? $el['elements'] : array();
			$el['elements'] = ( isset( $el['elType'] ) && 'widget' === $el['elType'] )
				? array()
				: self::normalize( $children, $path, true );
			$out[]          = $el;
		}
		return $out;
	}

	/**
	 * Dimension helper: dims( 16 ) or dims( 8, 16 ) or dims( 8, 16, 8, 16 ).
	 *
	 * @return array
	 */
	public static function dims() {
		$a = func_get_args();
		if ( 1 === count( $a ) ) {
			$a = array( $a[0], $a[0], $a[0], $a[0] );
		} elseif ( 2 === count( $a ) ) {
			$a = array( $a[0], $a[1], $a[0], $a[1] );
		}
		return array(
			'unit'     => 'px',
			'top'      => (string) $a[0],
			'right'    => (string) $a[1],
			'bottom'   => (string) $a[2],
			'left'     => (string) $a[3],
			'isLinked' => ( $a[0] === $a[1] && $a[1] === $a[2] && $a[2] === $a[3] ),
		);
	}

	/**
	 * Gap helper.
	 *
	 * @param int $px Gap in px.
	 * @return array
	 */
	public static function gap( $px ) {
		return array(
			'unit'     => 'px',
			'size'     => (int) $px,
			'column'   => (string) $px,
			'row'      => (string) $px,
			'isLinked' => true,
		);
	}

	/**
	 * Folders that are scanned for recipes.
	 *
	 * @return array
	 */
	public static function recipe_dirs() {
		$dirs = apply_filters( 'lyra_recipe_dirs', array( LYRA_PATH . 'includes/recipes/' ) );
		return is_array( $dirs ) ? $dirs : array();
	}

	/**
	 * Load all recipes (*.php returning an array, *.json).
	 *
	 * @return array<string,array>
	 */
	public static function recipes() {
		$recipes = array();
		foreach ( self::recipe_dirs() as $dir ) {
			$dir   = trailingslashit( $dir );
			$php   = glob( $dir . '*.php' );
			$json  = glob( $dir . '*.json' );
			$files = array_merge( is_array( $php ) ? $php : array(), is_array( $json ) ? $json : array() );
			sort( $files );
			foreach ( $files as $file ) {
				$base = basename( $file );
				if ( 'index.php' === $base ) {
					continue;
				}
				self::$counter = 0;
				if ( '.json' === substr( $base, -5 ) ) {
					$recipe = json_decode( (string) file_get_contents( $file ), true ); // phpcs:ignore WordPress.WP.AlternativeFunctions
				} else {
					$recipe = include $file;
				}
				if ( is_array( $recipe ) && ! empty( $recipe['slug'] ) && isset( $recipe['elements'] ) && is_array( $recipe['elements'] ) ) {
					$recipes[ $recipe['slug'] ] = $recipe;
				}
			}
		}
		return $recipes;
	}

	/**
	 * Find the post previously generated for a recipe slug.
	 *
	 * @param string $slug Recipe slug.
	 * @return int Post ID or 0.
	 */
	public static function find_page( $slug ) {
		$ids = get_posts(
			array(
				'post_type'      => array( 'page', 'elementor_library' ),
				'post_status'    => 'any',
				'meta_key'       => self::META_RECIPE, // phpcs:ignore WordPress.DB.SlowDBQuery
				'meta_value'     => $slug,             // phpcs:ignore WordPress.DB.SlowDBQuery
				'fields'         => 'ids',
				'posts_per_page' => 1,
			)
		);
		return $ids ? (int) $ids[0] : 0;
	}

	/**
	 * Has the user edited the generated post since we built it?
	 *
	 * @param int $post_id Post ID.
	 * @return bool
	 */
	public static function was_edited( $post_id ) {
		$stored = get_post_meta( $post_id, self::META_HASH, true );
		$data   = get_post_meta( $post_id, '_elementor_data', true );
		if ( ! $stored || ! is_string( $data ) ) {
			return false;
		}
		return md5( $data ) !== $stored;
	}

	/**
	 * Standard failure result.
	 *
	 * @param string $message Message.
	 * @param int    $id      Post id if known.
	 * @return array
	 */
	private static function fail( $message, $id = 0 ) {
		lyra_log( $message );
		return array(
			'ok'      => false,
			'id'      => (int) $id,
			'message' => $message,
		);
	}

	/**
	 * Build (or rebuild) one recipe.
	 *
	 * @param string $slug  Recipe slug.
	 * @param bool   $force Overwrite even if the user edited the post.
	 * @return array{ok:bool,id:int,message:string}
	 */
	public static function build( $slug, $force = false ) {
		if ( ! did_action( 'elementor/loaded' ) ) {
			return self::fail( __( 'Elementor is not active.', 'lyra-core' ) );
		}

		$recipes = self::recipes();
		if ( empty( $recipes[ $slug ] ) ) {
			return self::fail( __( 'Unknown recipe.', 'lyra-core' ) );
		}
		$recipe = $recipes[ $slug ];

		$is_template   = ( isset( $recipe['kind'] ) && 'template' === $recipe['kind'] );
		$template_type = isset( $recipe['template_type'] ) ? sanitize_key( $recipe['template_type'] ) : 'section';

		if ( $is_template ) {
			if ( ! post_type_exists( 'elementor_library' ) ) {
				return self::fail( __( 'Elementor templates are not available.', 'lyra-core' ) );
			}
			if ( in_array( $template_type, self::PRO_TYPES, true ) && ! defined( 'ELEMENTOR_PRO_VERSION' ) ) {
				return self::fail( __( 'This template type needs Elementor Pro (Theme Builder).', 'lyra-core' ) );
			}
		}

		$existing = self::find_page( $slug );
		if ( $existing && ! $force && self::was_edited( $existing ) ) {
			return self::fail( __( 'Edited manually; use "force" to overwrite.', 'lyra-core' ), $existing );
		}

		$data = self::normalize( $recipe['elements'], $slug );
		$json = wp_json_encode( $data );
		if ( false === $json ) {
			return self::fail( __( 'Could not encode page data.', 'lyra-core' ) );
		}

		$default_status = $is_template ? 'publish' : 'draft';
		$args           = array(
			'post_type'    => $is_template ? 'elementor_library' : 'page',
			'post_title'   => isset( $recipe['title'] ) ? $recipe['title'] : $slug,
			'post_status'  => isset( $recipe['status'] ) ? $recipe['status'] : $default_status,
			'post_content' => '',
		);

		if ( $existing ) {
			$args['ID'] = $existing;
			$post_id    = wp_update_post( wp_slash( $args ), true );
		} else {
			$args['post_name'] = sanitize_title( isset( $recipe['page_slug'] ) ? $recipe['page_slug'] : $slug );
			$post_id           = wp_insert_post( wp_slash( $args ), true );
		}

		if ( is_wp_error( $post_id ) ) {
			return self::fail( $post_id->get_error_message() );
		}

		update_post_meta( $post_id, '_elementor_edit_mode', 'builder' );
		update_post_meta( $post_id, '_elementor_version', defined( 'ELEMENTOR_VERSION' ) ? ELEMENTOR_VERSION : '3.0.0' );

		if ( $is_template ) {
			update_post_meta( $post_id, '_elementor_template_type', $template_type );
			wp_set_object_terms( $post_id, $template_type, 'elementor_library_type' );
			if ( ! empty( $recipe['conditions'] ) ) {
				update_post_meta( $post_id, '_elementor_conditions', array_values( (array) $recipe['conditions'] ) );
			}
		} else {
			$template = isset( $recipe['template'] ) ? $recipe['template'] : 'elementor_header_footer';
			update_post_meta( $post_id, '_elementor_template_type', 'wp-page' );
			update_post_meta( $post_id, '_wp_page_template', $template );
		}

		update_post_meta( $post_id, '_elementor_data', wp_slash( $json ) );
		update_post_meta( $post_id, self::META_RECIPE, $slug );
		update_post_meta( $post_id, self::META_HASH, md5( $json ) );

		if ( ! empty( $recipe['page_settings'] ) && is_array( $recipe['page_settings'] ) ) {
			update_post_meta( $post_id, '_elementor_page_settings', $recipe['page_settings'] );
		}

		self::clear_cache();
		if ( $is_template && ! empty( $recipe['conditions'] ) ) {
			self::refresh_conditions();
		}

		$message = $existing ? __( 'Updated.', 'lyra-core' ) : __( 'Created.', 'lyra-core' );
		lyra_log( $slug . ': ' . $message );

		return array(
			'ok'      => true,
			'id'      => (int) $post_id,
			'message' => $message,
		);
	}

	/**
	 * Build every recipe.
	 *
	 * @param bool $force Overwrite edited posts.
	 * @return array<string,array>
	 */
	public static function build_all( $force = false ) {
		$results = array();
		foreach ( array_keys( self::recipes() ) as $slug ) {
			$results[ $slug ] = self::build( $slug, $force );
		}
		return $results;
	}

	/**
	 * Assign a generated page as a WooCommerce page.
	 *
	 * @param string $slug Recipe slug.
	 * @return bool
	 */
	public static function assign_woo_page( $slug ) {
		$recipes = self::recipes();
		if ( empty( $recipes[ $slug ]['woo_page'] ) ) {
			return false;
		}
		$id = self::find_page( $slug );
		if ( ! $id ) {
			return false;
		}
		$map = array(
			'cart'     => 'woocommerce_cart_page_id',
			'checkout' => 'woocommerce_checkout_page_id',
		);
		$key = $recipes[ $slug ]['woo_page'];
		if ( ! isset( $map[ $key ] ) ) {
			return false;
		}
		update_option( $map[ $key ], $id );
		return true;
	}

	/**
	 * Clear Elementor CSS cache so new data renders.
	 */
	private static function clear_cache() {
		if ( class_exists( '\Elementor\Plugin' ) && ! empty( \Elementor\Plugin::$instance->files_manager ) ) {
			\Elementor\Plugin::$instance->files_manager->clear_cache();
		}
	}

	/**
	 * Ask Elementor Pro to rebuild its display-conditions cache.
	 * Guarded and best effort: internals can change between Pro versions,
	 * so verify on the live site (Templates > Theme Builder shows the conditions).
	 */
	private static function refresh_conditions() {
		if ( ! class_exists( '\ElementorPro\Plugin' ) ) {
			return;
		}
		try {
			$pro = \ElementorPro\Plugin::instance();
			if ( empty( $pro->modules_manager ) || ! method_exists( $pro->modules_manager, 'get_modules' ) ) {
				return;
			}
			$module = $pro->modules_manager->get_modules( 'theme-builder' );
			if ( $module && method_exists( $module, 'get_conditions_manager' ) ) {
				$manager = $module->get_conditions_manager();
				if ( $manager && method_exists( $manager, 'get_cache' ) ) {
					$manager->get_cache()->regenerate();
				}
			}
		} catch ( \Throwable $e ) {
			lyra_log( 'conditions cache: ' . $e->getMessage() );
		}
	}
}
