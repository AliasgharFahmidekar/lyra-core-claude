<?php
/**
 * Plugin Name: Lyra Core
 * Description: Generic foundation for building dynamic, editable Elementor (Pro) pages from code: idempotent page builder, widget registry, dynamic tags and an admin screen.
 * Version: 0.1.0
 * Requires at least: 5.8
 * Requires PHP: 7.4
 * Author: Lyra
 * License: GPL-2.0-or-later
 * Text Domain: lyra-core
 * Domain Path: /languages
 *
 * IMPORTANT: this file must only require files that do NOT extend Elementor
 * classes. Elementor-dependent files are loaded lazily (see Lyra_Elementor).
 *
 * @package LyraCore
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

define( 'LYRA_VERSION', '0.1.0' );
define( 'LYRA_FILE', __FILE__ );
define( 'LYRA_PATH', plugin_dir_path( __FILE__ ) );
define( 'LYRA_URL', plugin_dir_url( __FILE__ ) );
define( 'LYRA_BASENAME', plugin_basename( __FILE__ ) );

require_once LYRA_PATH . 'includes/functions.php';
require_once LYRA_PATH . 'includes/class-lyra-icons.php';
require_once LYRA_PATH . 'includes/class-lyra-page-builder.php';
require_once LYRA_PATH . 'includes/class-lyra-woo.php';
require_once LYRA_PATH . 'includes/class-lyra-assets.php';
require_once LYRA_PATH . 'includes/class-lyra-settings.php';
require_once LYRA_PATH . 'includes/class-lyra-elementor.php';
require_once LYRA_PATH . 'includes/class-lyra-plugin.php';

register_activation_hook( __FILE__, array( 'Lyra_Plugin', 'activate' ) );
add_action( 'before_woocommerce_init', array( 'Lyra_Plugin', 'declare_compat' ) );
add_action( 'plugins_loaded', array( 'Lyra_Plugin', 'boot' ), 20 );
