<?php
/**
 * Dynamic tags. LAZY-LOADED from Lyra_Elementor::register_tags().
 * Never require this file from the main plugin file.
 *
 * Example tag: prints a value from the plugin settings option (key chosen in the tag).
 * Copy this class to add tags (CPT/ACF/WooCommerce values, formatted dates, ...).
 * Use one of the TEXT / URL / IMAGE / NUMBER ... categories from
 * \Elementor\Modules\DynamicTags\Module so Elementor offers the tag on the right controls.
 *
 * @package LyraCore
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class Lyra_Tag_Option extends \Elementor\Core\DynamicTags\Tag {

	public function get_name() {
		return 'lyra-option';
	}

	public function get_title() {
		return __( 'Lyra option', 'lyra-core' );
	}

	public function get_group() {
		return 'lyra';
	}

	public function get_categories() {
		return array( \Elementor\Modules\DynamicTags\Module::TEXT_CATEGORY );
	}

	protected function register_controls() {
		$this->add_control(
			'key',
			array(
				'label' => __( 'Option key', 'lyra-core' ),
				'type'  => \Elementor\Controls_Manager::TEXT,
			)
		);
	}

	// Elementor < 3.1 compatibility.
	protected function _register_controls() { // phpcs:ignore PSR2.Methods.MethodDeclaration.Underscore
		$this->register_controls();
	}

	public function render() {
		$key = (string) $this->get_settings( 'key' );
		if ( '' === $key ) {
			return;
		}
		$value = lyra_option( $key, '' );
		if ( is_scalar( $value ) ) {
			echo wp_kses_post( (string) $value );
		}
	}
}
