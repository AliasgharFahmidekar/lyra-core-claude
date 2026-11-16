<?php
/**
 * Registers styles/scripts. Widgets declare them through get_style_depends()
 * / get_script_depends() so Elementor loads them only where used.
 *
 * @package LyraCore
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class Lyra_Assets {

	public function __construct() {
		add_action( 'wp_enqueue_scripts', array( $this, 'register' ), 5 );
		add_action( 'elementor/frontend/after_register_styles', array( $this, 'register' ) );
		add_action( 'elementor/frontend/after_register_scripts', array( $this, 'register' ) );
		add_action( 'elementor/editor/after_enqueue_styles', array( $this, 'register' ) );
	}

	/**
	 * Register handles (idempotent).
	 */
	public function register() {
		if ( ! wp_style_is( 'lyra-tokens', 'registered' ) ) {
			wp_register_style( 'lyra-tokens', LYRA_URL . 'assets/css/tokens.css', array(), LYRA_VERSION );
		}
		if ( ! wp_style_is( 'lyra-components', 'registered' ) ) {
			wp_register_style( 'lyra-components', LYRA_URL . 'assets/css/components.css', array( 'lyra-tokens' ), LYRA_VERSION );
		}
		if ( ! wp_script_is( 'lyra-frontend', 'registered' ) ) {
			wp_register_script( 'lyra-frontend', LYRA_URL . 'assets/js/frontend.js', array(), LYRA_VERSION, true );
			wp_localize_script(
				'lyra-frontend',
				'LyraConfig',
				array(
					'ajaxUrl' => admin_url( 'admin-ajax.php' ),
					'rtl'     => is_rtl(),
					'i18n'    => array(),
				)
			);
		}
	}
}
