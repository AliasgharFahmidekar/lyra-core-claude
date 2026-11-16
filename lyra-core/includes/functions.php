<?php
/**
 * Helpers. No Elementor class is extended here, safe to load early.
 *
 * @package LyraCore
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Read one value from the plugin settings option.
 *
 * @param string $key     Setting key.
 * @param mixed  $default Default value.
 * @return mixed
 */
function lyra_option( $key, $default = '' ) {
	$opts = get_option( 'lyra_settings', array() );
	return ( is_array( $opts ) && isset( $opts[ $key ] ) ) ? $opts[ $key ] : $default;
}

/**
 * Convert Latin digits to Persian digits when the setting is enabled.
 * Route every displayed number/price through this helper.
 *
 * @param mixed $value Value to format.
 * @return string
 */
function lyra_digits( $value ) {
	$value = (string) $value;
	if ( ! lyra_option( 'persian_digits', 0 ) ) {
		return $value;
	}
	return strtr(
		$value,
		array(
			'0' => '۰',
			'1' => '۱',
			'2' => '۲',
			'3' => '۳',
			'4' => '۴',
			'5' => '۵',
			'6' => '۶',
			'7' => '۷',
			'8' => '۸',
			'9' => '۹',
		)
	);
}

/**
 * Append a line to the plugin log (last 50 entries, shown in the admin screen).
 *
 * @param string $message Message.
 */
function lyra_log( $message ) {
	$log = get_option( 'lyra_log', array() );
	if ( ! is_array( $log ) ) {
		$log = array();
	}
	array_unshift(
		$log,
		array(
			'time'    => current_time( 'mysql' ),
			'message' => (string) $message,
		)
	);
	update_option( 'lyra_log', array_slice( $log, 0, 50 ), false );
}

/**
 * Get the plugin log.
 *
 * @return array
 */
function lyra_get_log() {
	$log = get_option( 'lyra_log', array() );
	return is_array( $log ) ? $log : array();
}

/**
 * Is the current request the Elementor editor or its preview iframe?
 *
 * @return bool
 */
function lyra_is_editor() {
	if ( ! class_exists( '\Elementor\Plugin' ) || empty( \Elementor\Plugin::$instance->editor ) ) {
		return false;
	}
	if ( \Elementor\Plugin::$instance->editor->is_edit_mode() ) {
		return true;
	}
	return ! empty( \Elementor\Plugin::$instance->preview ) && \Elementor\Plugin::$instance->preview->is_preview_mode();
}

/**
 * Render an Elementor ICONS control value, or a bundled SVG fallback.
 *
 * @param mixed  $icon     Value of an Elementor ICONS control.
 * @param string $fallback Name of a bundled icon (see Lyra_Icons).
 * @param array  $attrs    Extra attributes for Elementor's renderer.
 */
function lyra_render_icon( $icon, $fallback = '', $attrs = array() ) {
	if ( is_array( $icon ) && ! empty( $icon['value'] ) && class_exists( '\Elementor\Icons_Manager' ) ) {
		\Elementor\Icons_Manager::render_icon( $icon, array_merge( array( 'aria-hidden' => 'true' ), $attrs ) );
		return;
	}
	if ( $fallback ) {
		echo Lyra_Icons::get( $fallback ); // phpcs:ignore WordPress.Security.EscapeOutput -- static, escaped inside.
	}
}
