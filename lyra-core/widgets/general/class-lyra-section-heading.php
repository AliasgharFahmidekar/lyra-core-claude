<?php
/**
 * Section heading: eyebrow + title + description + optional button.
 * Reference widget: copy its structure for new widgets.
 *
 * @package LyraCore
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class Lyra_Section_Heading extends Lyra_Base_Widget {

	public function get_name() {
		return 'lyra-section-heading';
	}

	public function get_title() {
		return __( 'Section Heading', 'lyra-core' );
	}

	public function get_icon() {
		return 'eicon-heading';
	}

	public function get_keywords() {
		return array( 'lyra', 'heading', 'title', 'section' );
	}

	protected function register_controls() {
		$this->start_controls_section(
			'content',
			array( 'label' => __( 'Content', 'lyra-core' ) )
		);

		$this->add_control(
			'eyebrow',
			array(
				'label'   => __( 'Eyebrow', 'lyra-core' ),
				'type'    => \Elementor\Controls_Manager::TEXT,
				'default' => __( 'Eyebrow', 'lyra-core' ),
				'dynamic' => array( 'active' => true ),
			)
		);
		$this->add_control(
			'title',
			array(
				'label'   => __( 'Title', 'lyra-core' ),
				'type'    => \Elementor\Controls_Manager::TEXT,
				'default' => __( 'Section title', 'lyra-core' ),
				'dynamic' => array( 'active' => true ),
			)
		);
		$this->add_control(
			'title_tag',
			array(
				'label'   => __( 'Title HTML tag', 'lyra-core' ),
				'type'    => \Elementor\Controls_Manager::SELECT,
				'options' => array(
					'h1'  => 'H1',
					'h2'  => 'H2',
					'h3'  => 'H3',
					'h4'  => 'H4',
					'div' => 'div',
				),
				'default' => 'h2',
			)
		);
		$this->add_control(
			'description',
			array(
				'label'   => __( 'Description', 'lyra-core' ),
				'type'    => \Elementor\Controls_Manager::TEXTAREA,
				'default' => __( 'A short supporting sentence.', 'lyra-core' ),
				'dynamic' => array( 'active' => true ),
			)
		);
		$this->add_control(
			'show_button',
			array(
				'label'        => __( 'Show button', 'lyra-core' ),
				'type'         => \Elementor\Controls_Manager::SWITCHER,
				'return_value' => 'yes',
				'default'      => '',
			)
		);
		$this->add_control(
			'button_text',
			array(
				'label'     => __( 'Button text', 'lyra-core' ),
				'type'      => \Elementor\Controls_Manager::TEXT,
				'default'   => __( 'Learn more', 'lyra-core' ),
				'condition' => array( 'show_button' => 'yes' ),
			)
		);
		$this->add_control(
			'button_link',
			array(
				'label'     => __( 'Button link', 'lyra-core' ),
				'type'      => \Elementor\Controls_Manager::URL,
				'dynamic'   => array( 'active' => true ),
				'condition' => array( 'show_button' => 'yes' ),
			)
		);
		$this->add_control(
			'align',
			array(
				'label'     => __( 'Alignment', 'lyra-core' ),
				'type'      => \Elementor\Controls_Manager::CHOOSE,
				'options'   => array(
					'start'  => array(
						'title' => __( 'Start', 'lyra-core' ),
						'icon'  => 'eicon-text-align-left',
					),
					'center' => array(
						'title' => __( 'Center', 'lyra-core' ),
						'icon'  => 'eicon-text-align-center',
					),
					'end'    => array(
						'title' => __( 'End', 'lyra-core' ),
						'icon'  => 'eicon-text-align-right',
					),
				),
				'selectors' => array(
					'{{WRAPPER}} .lyra-section-heading' => 'text-align: {{VALUE}};',
				),
			)
		);

		$this->end_controls_section();

		$this->add_text_style( 'eyebrow', __( 'Eyebrow', 'lyra-core' ), '.lyra-section-heading__eyebrow' );
		$this->add_text_style( 'title', __( 'Title', 'lyra-core' ), '.lyra-section-heading__title' );
		$this->add_text_style( 'description', __( 'Description', 'lyra-core' ), '.lyra-section-heading__description' );
	}

	protected function render() {
		$title_tag = $this->s( 'title_tag', 'h2' );
		if ( ! in_array( $title_tag, array( 'h1', 'h2', 'h3', 'h4', 'div' ), true ) ) {
			$title_tag = 'h2';
		}

		echo '<div class="lyra-widget lyra-section-heading">';

		if ( $this->s( 'eyebrow' ) ) {
			echo '<span class="lyra-section-heading__eyebrow">' . esc_html( $this->s( 'eyebrow' ) ) . '</span>';
		}
		if ( $this->s( 'title' ) ) {
			printf(
				'<%1$s class="lyra-section-heading__title">%2$s</%1$s>',
				tag_escape( $title_tag ),
				esc_html( $this->s( 'title' ) )
			);
		}
		if ( $this->s( 'description' ) ) {
			echo '<p class="lyra-section-heading__description">' . wp_kses_post( $this->s( 'description' ) ) . '</p>';
		}

		if ( 'yes' === $this->s( 'show_button' ) && $this->s( 'button_text' ) ) {
			$link = $this->s( 'button_link', array() );
			$this->add_render_attribute( 'button', 'class', 'lyra-btn' );
			if ( is_array( $link ) && ! empty( $link['url'] ) ) {
				$this->add_link_attributes( 'button', $link );
			}
			echo '<a ' . $this->get_render_attribute_string( 'button' ) . '>' . esc_html( $this->s( 'button_text' ) ) . '</a>'; // phpcs:ignore WordPress.Security.EscapeOutput
		}

		echo '</div>';
	}
}
