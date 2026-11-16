<?php
/**
 * Base class for every Lyra widget. LAZY-LOADED: required only from inside
 * Elementor's widget registration hook (see Lyra_Elementor::register_widgets).
 * Never require this file from the main plugin file.
 *
 * @package LyraCore
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

abstract class Lyra_Base_Widget extends \Elementor\Widget_Base {

	public function get_categories() {
		return array( Lyra_Elementor::CATEGORY );
	}

	public function get_icon() {
		return 'eicon-code';
	}

	public function get_keywords() {
		return array( 'lyra' );
	}

	public function get_style_depends() {
		return array( 'lyra-tokens', 'lyra-components' );
	}

	public function get_script_depends() {
		return array( 'lyra-frontend' );
	}

	/**
	 * Elementor < 3.1 calls _register_controls(); newer versions call
	 * register_controls(). Concrete widgets only implement register_controls().
	 */
	protected function _register_controls() { // phpcs:ignore PSR2.Methods.MethodDeclaration.Underscore
		$this->register_controls();
	}

	/**
	 * Read one setting (display value, dynamic tags resolved).
	 *
	 * @param string $key     Control id.
	 * @param mixed  $default Fallback.
	 * @return mixed
	 */
	protected function s( $key, $default = '' ) {
		$value = $this->get_settings_for_display( $key );
		return ( null === $value || '' === $value ) ? $default : $value;
	}

	/**
	 * Read a repeater item field.
	 *
	 * @param array  $item    Repeater item.
	 * @param string $key     Field id.
	 * @param mixed  $default Fallback.
	 * @return mixed
	 */
	protected function field( $item, $key, $default = '' ) {
		return ( is_array( $item ) && isset( $item[ $key ] ) && '' !== $item[ $key ] ) ? $item[ $key ] : $default;
	}

	/**
	 * Add a Style-tab section with color + typography for one selector.
	 *
	 * @param string $id       Unique id prefix (e.g. 'title').
	 * @param string $label    Section label.
	 * @param string $selector CSS selector inside the widget (e.g. '.lyra-x__title').
	 */
	protected function add_text_style( $id, $label, $selector ) {
		$this->start_controls_section(
			$id . '_style',
			array(
				'label' => $label,
				'tab'   => \Elementor\Controls_Manager::TAB_STYLE,
			)
		);
		$this->add_control(
			$id . '_color',
			array(
				'label'     => __( 'Color', 'lyra-core' ),
				'type'      => \Elementor\Controls_Manager::COLOR,
				'selectors' => array(
					'{{WRAPPER}} ' . $selector => 'color: {{VALUE}};',
				),
			)
		);
		$this->add_group_control(
			\Elementor\Group_Control_Typography::get_type(),
			array(
				'name'     => $id . '_typography',
				'selector' => '{{WRAPPER}} ' . $selector,
			)
		);
		$this->end_controls_section();
	}
}
