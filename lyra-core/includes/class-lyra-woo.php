<?php
/**
 * WooCommerce helpers (optional dependency; every call is guarded).
 * Widgets are VIEWS over WooCommerce state; Woo owns cart/checkout/orders.
 *
 * @package LyraCore
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class Lyra_Woo {

	/**
	 * Is WooCommerce loaded?
	 *
	 * @return bool
	 */
	public static function is_active() {
		return class_exists( 'WooCommerce' ) && function_exists( 'WC' );
	}

	/**
	 * Cart object or null (it can be null in admin/REST/editor).
	 *
	 * @return WC_Cart|null
	 */
	public static function cart() {
		if ( ! self::is_active() ) {
			return null;
		}
		$wc = WC();
		return ( $wc && ! empty( $wc->cart ) ) ? $wc->cart : null;
	}

	/**
	 * Formatted price through one helper (Persian digits aware).
	 *
	 * @param float $amount Amount.
	 * @return string
	 */
	public static function price( $amount ) {
		if ( function_exists( 'wc_price' ) ) {
			return lyra_digits( wp_strip_all_tags( wc_price( $amount ) ) );
		}
		return lyra_digits( number_format_i18n( (float) $amount, 0 ) );
	}

	/**
	 * Should widgets show fixed demo data (editor only, never on the live front end)?
	 *
	 * @return bool
	 */
	public static function use_demo_data() {
		return lyra_is_editor();
	}
}
