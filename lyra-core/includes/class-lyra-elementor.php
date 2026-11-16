<?php
/**
 * Elementor integration: category, widget manifest, registration, dynamic tags.
 * This file itself does not extend Elementor classes; the files it loads do,
 * and they are only required from inside Elementor's registration hooks.
 *
 * @package LyraCore
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class Lyra_Elementor {

	const CATEGORY = 'lyra-core';

	/**
	 * Widget manifest: class => path relative to widgets/.
	 * Adding a widget = add the file and one line here
	 * (or run scripts/new_widget.py which does both).
	 *
	 * @var array
	 */
	public static $manifest = array(
		'Lyra_Section_Heading' => 'general/class-lyra-section-heading.php',
		'Lyra_Feature_Grid'    => 'general/class-lyra-feature-grid.php',
	);

	public function __construct() {
		$new_api = defined( 'ELEMENTOR_VERSION' ) && version_compare( ELEMENTOR_VERSION, '3.5.0', '>=' );

		add_action( 'elementor/elements/categories_registered', array( $this, 'register_category' ) );

		if ( $new_api ) {
			add_action( 'elementor/widgets/register', array( $this, 'register_widgets' ) );
			add_action( 'elementor/dynamic_tags/register', array( $this, 'register_tags' ) );
		} else {
			add_action( 'elementor/widgets/widgets_registered', array( $this, 'register_widgets' ) );
			add_action( 'elementor/dynamic_tags/register_tags', array( $this, 'register_tags' ) );
		}
	}

	/**
	 * Manifest after filters (lets other plugins/themes add widgets).
	 *
	 * @return array
	 */
	public static function manifest() {
		$list = apply_filters( 'lyra_widget_manifest', self::$manifest );
		return is_array( $list ) ? $list : array();
	}

	/**
	 * Register the widget category.
	 *
	 * @param object $elements_manager Elementor elements manager.
	 */
	public function register_category( $elements_manager ) {
		$elements_manager->add_category(
			self::CATEGORY,
			array(
				'title' => __( 'Lyra Core', 'lyra-core' ),
				'icon'  => 'eicon-font',
			)
		);
	}

	/**
	 * Load and register every widget in the manifest.
	 *
	 * @param object $manager Elementor widgets manager.
	 */
	public function register_widgets( $manager ) {
		require_once LYRA_PATH . 'includes/class-lyra-base-widget.php';

		foreach ( self::manifest() as $class => $file ) {
			$path = LYRA_PATH . 'widgets/' . $file;
			if ( ! file_exists( $path ) ) {
				lyra_log( 'Widget file missing: ' . $file );
				continue;
			}
			require_once $path;
			if ( ! class_exists( $class ) ) {
				lyra_log( 'Widget class missing: ' . $class );
				continue;
			}
			$widget = new $class();
			if ( method_exists( $manager, 'register' ) ) {
				$manager->register( $widget );
			} else {
				$manager->register_widget_type( $widget );
			}
		}
	}

	/**
	 * Register the dynamic tag group and the tags.
	 *
	 * @param object $tags_manager Elementor dynamic tags manager.
	 */
	public function register_tags( $tags_manager ) {
		require_once LYRA_PATH . 'includes/class-lyra-dynamic-tags.php';

		$tags_manager->register_group(
			'lyra',
			array( 'title' => __( 'Lyra Core', 'lyra-core' ) )
		);

		if ( method_exists( $tags_manager, 'register' ) ) {
			$tags_manager->register( new Lyra_Tag_Option() );
		} else {
			$tags_manager->register_tag( 'Lyra_Tag_Option' );
		}
	}
}
