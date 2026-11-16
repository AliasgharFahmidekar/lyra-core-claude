<?php
/**
 * Example recipe: a tiny landing page. Copy this file for every real page.
 * Keep generated data deterministic (no random values) so rebuilds and the
 * "edited manually" check keep working.
 *
 * @package LyraCore
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

$b = 'Lyra_Page_Builder';

return array(
	'slug'     => 'example-landing',
	'title'    => 'Example landing',
	'kind'     => 'page',
	'status'   => 'draft',
	'template' => 'elementor_header_footer',
	'elements' => array(
		$b::container(
			array(
				'content_width'  => 'boxed',
				'boxed_width'    => array(
					'unit' => 'px',
					'size' => 1140,
				),
				'flex_direction' => 'column',
				'flex_gap'       => $b::gap( 48 ),
				'padding'        => $b::dims( 64, 16 ),
			),
			array(
				$b::widget(
					'lyra-section-heading',
					array(
						'eyebrow'     => 'Example',
						'title'       => 'Build pages from code',
						'description' => 'Every text, icon and link below stays editable in Elementor.',
						'show_button' => 'yes',
						'button_text' => 'Get started',
						'button_link' => array(
							'url'         => '#features',
							'is_external' => '',
							'nofollow'    => '',
						),
					)
				),
				$b::widget(
					'lyra-feature-grid',
					array(
						'items' => array(
							$b::item(
								array(
									'icon'  => array(
										'value'   => 'fas fa-bolt',
										'library' => 'fa-solid',
									),
									'title' => 'Fast',
									'text'  => 'Idempotent rebuilds from one recipe file.',
								)
							),
							$b::item(
								array(
									'icon'  => array(
										'value'   => 'fas fa-pen',
										'library' => 'fa-solid',
									),
									'title' => 'Editable',
									'text'  => 'Pages open in the Elementor editor as usual.',
								)
							),
							$b::item(
								array(
									'icon'  => array(
										'value'   => 'fas fa-database',
										'library' => 'fa-solid',
									),
									'title' => 'Dynamic',
									'text'  => 'Bind controls to dynamic tags (Elementor Pro).',
								)
							),
						),
					)
				),
			)
		),
	),
);
