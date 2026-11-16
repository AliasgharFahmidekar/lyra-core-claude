<?php
/**
 * Bootstrap: dependency checks, lazy Elementor integration.
 *
 * @package LyraCore
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class Lyra_Plugin {

	/**
	 * Activation: default options only. Pages are never created automatically;
	 * the admin button does that so failures are visible.
	 */
	public static function activate() {
		if ( false === get_option( 'lyra_settings', false ) ) {
			add_option(
				'lyra_settings',
				array(
					'delete_on_uninstall' => 0,
					'persian_digits'      => 0,
				)
			);
		}
	}

	/**
	 * Declare HPOS compatibility for WooCommerce.
	 */
	public static function declare_compat() {
		if ( class_exists( '\Automattic\WooCommerce\Utilities\FeaturesUtil' ) ) {
			\Automattic\WooCommerce\Utilities\FeaturesUtil::declare_compatibility( 'custom_order_tables', LYRA_FILE, true );
		}
	}

	/**
	 * Runs on plugins_loaded (priority 20, after Elementor).
	 */
	public static function boot() {
		load_plugin_textdomain( 'lyra-core', false, dirname( LYRA_BASENAME ) . '/languages' );

		new Lyra_Settings();
		new Lyra_Assets();

		if ( ! did_action( 'elementor/loaded' ) ) {
			add_action( 'admin_notices', array( __CLASS__, 'notice_missing_elementor' ) );
			return;
		}

		new Lyra_Elementor();
	}

	public static function notice_missing_elementor() {
		if ( ! current_user_can( 'activate_plugins' ) ) {
			return;
		}
		echo '<div class="notice notice-warning"><p>' . esc_html__( 'Lyra Core needs Elementor to be installed and active.', 'lyra-core' ) . '</p></div>';
	}
}
