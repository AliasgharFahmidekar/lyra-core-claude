<?php
/**
 * Feature grid: responsive grid of icon + title + text items (repeater).
 * Reference widget for repeaters and responsive controls.
 *
 * @package LyraCore
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class Lyra_Feature_Grid extends Lyra_Base_Widget {

	public function get_name() {
		return 'lyra-feature-grid';
	}

	public function get_title() {
		return __( 'Feature Grid', 'lyra-core' );
	}

	public function get_icon() {
		return 'eicon-posts-grid';
	}

	public function get_keywords() {
		return array( 'lyra', 'features', 'grid', 'icons', 'cards' );
	}

	protected function register_controls() {
		$this->start_controls_section(
			'content',
			array( 'label' => __( 'Items', 'lyra-core' ) )
		);

		$repeater = new \Elementor\Repeater();
		$repeater->add_control(
			'icon',
			array(
				'label'   => __( 'Icon', 'lyra-core' ),
				'type'    => \Elementor\Controls_Manager::ICONS,
				'default' => array(
					'value'   => 'fas fa-star',
					'library' => 'fa-solid',
				),
			)
		);
		$repeater->add_control(
			'title',
			array(
				'label'   => __( 'Title', 'lyra-core' ),
				'type'    => \Elementor\Controls_Manager::TEXT,
				'default' => __( 'Feature title', 'lyra-core' ),
				'dynamic' => array( 'active' => true ),
			)
		);
		$repeater->add_control(
			'text',
			array(
				'label'   => __( 'Text', 'lyra-core' ),
				'type'    => \Elementor\Controls_Manager::TEXTAREA,
				'default' => __( 'Short description of the feature.', 'lyra-core' ),
				'dynamic' => array( 'active' => true ),
			)
		);
		$repeater->add_control(
			'link',
			array(
				'label'   => __( 'Link', 'lyra-core' ),
				'type'    => \Elementor\Controls_Manager::URL,
				'dynamic' => array( 'active' => true ),
			)
		);

		$this->add_control(
			'items',
			array(
				'label'       => __( 'Features', 'lyra-core' ),
				'type'        => \Elementor\Controls_Manager::REPEATER,
				'fields'      => $repeater->get_controls(),
				'default'     => array(
					array(
						'title' => __( 'Feature one', 'lyra-core' ),
						'text'  => __( 'Short description of the feature.', 'lyra-core' ),
					),
					array(
						'title' => __( 'Feature two', 'lyra-core' ),
						'text'  => __( 'Short description of the feature.', 'lyra-core' ),
					),
					array(
						'title' => __( 'Feature three', 'lyra-core' ),
						'text'  => __( 'Short description of the feature.', 'lyra-core' ),
					),
				),
				'title_field' => '{{{ title }}}',
			)
		);

		$this->add_responsive_control(
			'columns',
			array(
				'label'          => __( 'Columns', 'lyra-core' ),
				'type'           => \Elementor\Controls_Manager::SELECT,
				'options'        => array(
					'1' => '1',
					'2' => '2',
					'3' => '3',
					'4' => '4',
				),
				'default'        => '3',
				'tablet_default' => '2',
				'mobile_default' => '1',
				'selectors'      => array(
					'{{WRAPPER}} .lyra-feature-grid__list' => 'grid-template-columns: repeat({{VALUE}}, minmax(0, 1fr));',
				),
			)
		);

		$this->end_controls_section();

		$this->add_text_style( 'item_title', __( 'Item title', 'lyra-core' ), '.lyra-feature-grid__title' );
		$this->add_text_style( 'item_text', __( 'Item text', 'lyra-core' ), '.lyra-feature-grid__text' );
	}

	protected function render() {
		$items = $this->s( 'items', array() );
		if ( ! is_array( $items ) || empty( $items ) ) {
			return;
		}

		echo '<div class="lyra-widget lyra-feature-grid"><div class="lyra-feature-grid__list">';

		foreach ( $items as $item ) {
			$title = $this->field( $item, 'title' );
			$text  = $this->field( $item, 'text' );
			$link  = $this->field( $item, 'link', array() );
			$url   = ( is_array( $link ) && ! empty( $link['url'] ) ) ? $link['url'] : '';

			echo '<div class="lyra-feature-grid__item">';
			echo '<span class="lyra-feature-grid__icon">';
			lyra_render_icon( $this->field( $item, 'icon', array() ), 'star' );
			echo '</span>';

			if ( $title ) {
				echo '<h3 class="lyra-feature-grid__title">';
				if ( $url ) {
					echo '<a href="' . esc_url( $url ) . '">' . esc_html( $title ) . '</a>';
				} else {
					echo esc_html( $title );
				}
				echo '</h3>';
			}
			if ( $text ) {
				echo '<p class="lyra-feature-grid__text">' . wp_kses_post( $text ) . '</p>';
			}
			echo '</div>';
		}

		echo '</div></div>';
	}
}
