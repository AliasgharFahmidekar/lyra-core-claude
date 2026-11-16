<?php
/**
 * Uninstall: remove options; remove generated pages only if the user opted in.
 *
 * @package LyraCore
 */

if ( ! defined( 'WP_UNINSTALL_PLUGIN' ) ) {
	exit;
}

$lyra_opts = get_option( 'lyra_settings', array() );

if ( is_array( $lyra_opts ) && ! empty( $lyra_opts['delete_on_uninstall'] ) ) {
	$lyra_ids = get_posts(
		array(
			'post_type'      => array( 'page', 'elementor_library' ),
			'post_status'    => 'any',
			'meta_key'       => '_lyra_recipe', // phpcs:ignore WordPress.DB.SlowDBQuery
			'fields'         => 'ids',
			'posts_per_page' => -1,
		)
	);
	foreach ( $lyra_ids as $lyra_id ) {
		wp_delete_post( $lyra_id, true );
	}
}

delete_option( 'lyra_settings' );
delete_option( 'lyra_log' );
