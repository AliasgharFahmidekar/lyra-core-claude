<?php
/**
 * Tiny bundled SVG icon set (fallback when no Elementor icon is chosen).
 *
 * @package LyraCore
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class Lyra_Icons {

	/**
	 * Icon name => SVG path data (24x24, stroke based).
	 *
	 * @var array
	 */
	private static $paths = array(
		'arrow-right' => 'M5 12h14M13 6l6 6-6 6',
		'arrow-left'  => 'M19 12H5M11 6l-6 6 6 6',
		'check'       => 'M5 12l5 5L20 7',
		'star'        => 'M12 3l2.9 5.9 6.5.9-4.7 4.6 1.1 6.5L12 17.8 6.2 20.9l1.1-6.5L2.6 9.8l6.5-.9z',
		'phone'       => 'M5 4h4l2 5-2.5 1.5a11 11 0 0 0 5 5L15 13l5 2v4a2 2 0 0 1-2 2A16 16 0 0 1 3 6a2 2 0 0 1 2-2z',
		'mail'        => 'M4 6h16v12H4zM4 6l8 7 8-7',
		'menu'        => 'M4 7h16M4 12h16M4 17h16',
		'close'       => 'M6 6l12 12M18 6L6 18',
	);

	/**
	 * Get an icon as an SVG string.
	 *
	 * @param string $name Icon name.
	 * @param int    $size Pixel size.
	 * @return string
	 */
	public static function get( $name, $size = 24 ) {
		if ( ! isset( self::$paths[ $name ] ) ) {
			return '';
		}
		return sprintf(
			'<svg class="lyra-icon lyra-icon--%1$s" width="%2$d" height="%2$d" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true" focusable="false"><path d="%3$s"/></svg>',
			esc_attr( $name ),
			(int) $size,
			esc_attr( self::$paths[ $name ] )
		);
	}

	/**
	 * Names of bundled icons.
	 *
	 * @return array
	 */
	public static function names() {
		return array_keys( self::$paths );
	}
}
