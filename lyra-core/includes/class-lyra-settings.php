<?php
/**
 * Admin screen: dependency status, build pages from recipes, options, log.
 *
 * @package LyraCore
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class Lyra_Settings {

	const PAGE = 'lyra-core';

	public function __construct() {
		add_action( 'admin_menu', array( $this, 'menu' ) );
		add_action( 'admin_init', array( $this, 'register' ) );
		add_action( 'admin_post_lyra_build', array( $this, 'handle_build' ) );
		add_action( 'admin_post_lyra_assign_woo', array( $this, 'handle_assign_woo' ) );
	}

	public function menu() {
		add_menu_page(
			__( 'Lyra Core', 'lyra-core' ),
			__( 'Lyra Core', 'lyra-core' ),
			'manage_options',
			self::PAGE,
			array( $this, 'render' ),
			'dashicons-layout',
			58
		);
	}

	public function register() {
		register_setting(
			'lyra_core',
			'lyra_settings',
			array(
				'type'              => 'array',
				'sanitize_callback' => array( $this, 'sanitize' ),
				'default'           => array(),
			)
		);
	}

	/**
	 * Sanitize options.
	 *
	 * @param mixed $input Raw input.
	 * @return array
	 */
	public function sanitize( $input ) {
		$input = is_array( $input ) ? $input : array();
		return array(
			'delete_on_uninstall' => empty( $input['delete_on_uninstall'] ) ? 0 : 1,
			'persian_digits'      => empty( $input['persian_digits'] ) ? 0 : 1,
		);
	}

	public function handle_build() {
		if ( ! current_user_can( 'manage_options' ) ) {
			wp_die( esc_html__( 'Not allowed.', 'lyra-core' ) );
		}
		check_admin_referer( 'lyra_build' );

		$slug  = isset( $_POST['slug'] ) ? sanitize_key( wp_unslash( $_POST['slug'] ) ) : '';
		$force = ! empty( $_POST['force'] );

		if ( 'all' === $slug ) {
			$results = Lyra_Page_Builder::build_all( $force );
		} else {
			$results = array( $slug => Lyra_Page_Builder::build( $slug, $force ) );
		}

		set_transient( 'lyra_build_results_' . get_current_user_id(), $results, 60 );
		wp_safe_redirect( admin_url( 'admin.php?page=' . self::PAGE ) );
		exit;
	}

	public function handle_assign_woo() {
		if ( ! current_user_can( 'manage_options' ) ) {
			wp_die( esc_html__( 'Not allowed.', 'lyra-core' ) );
		}
		check_admin_referer( 'lyra_assign_woo' );

		$slug = isset( $_POST['slug'] ) ? sanitize_key( wp_unslash( $_POST['slug'] ) ) : '';
		$ok   = Lyra_Page_Builder::assign_woo_page( $slug );
		$msg  = $ok ? __( 'Assigned as WooCommerce page.', 'lyra-core' ) : __( 'Could not assign (build the page first).', 'lyra-core' );
		lyra_log( $slug . ': ' . $msg );

		set_transient(
			'lyra_build_results_' . get_current_user_id(),
			array(
				$slug => array(
					'ok'      => $ok,
					'id'      => 0,
					'message' => $msg,
				),
			),
			60
		);
		wp_safe_redirect( admin_url( 'admin.php?page=' . self::PAGE ) );
		exit;
	}

	/**
	 * Dependency status rows.
	 *
	 * @return array
	 */
	private function status_rows() {
		$container = null;
		if ( class_exists( '\Elementor\Plugin' ) && ! empty( \Elementor\Plugin::$instance->experiments ) ) {
			$container = \Elementor\Plugin::$instance->experiments->is_feature_active( 'container' );
		}
		return array(
			array( __( 'Elementor', 'lyra-core' ), did_action( 'elementor/loaded' ) ? 'ok' : 'missing', defined( 'ELEMENTOR_VERSION' ) ? ELEMENTOR_VERSION : '' ),
			array( __( 'Elementor Pro (Theme Builder, Loop, dynamic tags)', 'lyra-core' ), defined( 'ELEMENTOR_PRO_VERSION' ) ? 'ok' : 'optional', defined( 'ELEMENTOR_PRO_VERSION' ) ? ELEMENTOR_PRO_VERSION : '' ),
			array( __( 'Flexbox Container feature', 'lyra-core' ), true === $container ? 'ok' : 'check', '' ),
			array( __( 'WooCommerce', 'lyra-core' ), class_exists( 'WooCommerce' ) ? 'ok' : 'optional', defined( 'WC_VERSION' ) ? WC_VERSION : '' ),
			array( __( 'PHP', 'lyra-core' ), 'ok', PHP_VERSION ),
		);
	}

	public function render() {
		if ( ! current_user_can( 'manage_options' ) ) {
			return;
		}

		$recipes = Lyra_Page_Builder::recipes();
		$key     = 'lyra_build_results_' . get_current_user_id();
		$results = get_transient( $key );

		echo '<div class="wrap"><h1>' . esc_html__( 'Lyra Core', 'lyra-core' ) . '</h1>';

		if ( is_array( $results ) ) {
			delete_transient( $key );
			foreach ( $results as $slug => $r ) {
				printf(
					'<div class="notice %1$s"><p><strong>%2$s</strong>: %3$s</p></div>',
					! empty( $r['ok'] ) ? 'notice-success' : 'notice-error',
					esc_html( $slug ),
					esc_html( isset( $r['message'] ) ? $r['message'] : '' )
				);
			}
		}

		echo '<h2>' . esc_html__( 'Status', 'lyra-core' ) . '</h2><table class="widefat striped" style="max-width:720px"><tbody>';
		foreach ( $this->status_rows() as $row ) {
			printf(
				'<tr><td>%1$s</td><td><strong>%2$s</strong></td><td>%3$s</td></tr>',
				esc_html( $row[0] ),
				esc_html( $row[1] ),
				esc_html( $row[2] )
			);
		}
		echo '</tbody></table>';

		echo '<h2>' . esc_html__( 'Pages and templates', 'lyra-core' ) . '</h2>';
		if ( empty( $recipes ) ) {
			echo '<p>' . esc_html__( 'No recipes found in includes/recipes/.', 'lyra-core' ) . '</p>';
		} else {
			?>
			<form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>">
				<input type="hidden" name="action" value="lyra_build" />
				<?php wp_nonce_field( 'lyra_build' ); ?>
				<p>
					<button type="submit" class="button button-primary" name="slug" value="all"><?php esc_html_e( 'Build all', 'lyra-core' ); ?></button>
					<label style="margin-inline-start:12px"><input type="checkbox" name="force" value="1" /> <?php esc_html_e( 'Force overwrite manually edited pages', 'lyra-core' ); ?></label>
				</p>
				<table class="widefat striped">
					<thead><tr>
						<th><?php esc_html_e( 'Recipe', 'lyra-core' ); ?></th>
						<th><?php esc_html_e( 'Type', 'lyra-core' ); ?></th>
						<th><?php esc_html_e( 'State', 'lyra-core' ); ?></th>
						<th><?php esc_html_e( 'Actions', 'lyra-core' ); ?></th>
					</tr></thead>
					<tbody>
					<?php foreach ( $recipes as $slug => $recipe ) : ?>
						<?php
						$id    = Lyra_Page_Builder::find_page( $slug );
						$state = __( 'Not built', 'lyra-core' );
						if ( $id ) {
							$state = Lyra_Page_Builder::was_edited( $id ) ? __( 'Edited manually', 'lyra-core' ) : __( 'Built', 'lyra-core' );
						}
						$kind = ( isset( $recipe['kind'] ) && 'template' === $recipe['kind'] ) ? ( isset( $recipe['template_type'] ) ? $recipe['template_type'] : 'template' ) : 'page';
						?>
						<tr>
							<td><strong><?php echo esc_html( isset( $recipe['title'] ) ? $recipe['title'] : $slug ); ?></strong><br /><code><?php echo esc_html( $slug ); ?></code></td>
							<td><?php echo esc_html( $kind ); ?></td>
							<td><?php echo esc_html( $state ); ?></td>
							<td>
								<button type="submit" class="button" name="slug" value="<?php echo esc_attr( $slug ); ?>"><?php echo $id ? esc_html__( 'Rebuild', 'lyra-core' ) : esc_html__( 'Build', 'lyra-core' ); ?></button>
								<?php if ( $id ) : ?>
									<a class="button" href="<?php echo esc_url( admin_url( 'post.php?post=' . (int) $id . '&action=elementor' ) ); ?>"><?php esc_html_e( 'Edit with Elementor', 'lyra-core' ); ?></a>
								<?php endif; ?>
							</td>
						</tr>
					<?php endforeach; ?>
					</tbody>
				</table>
			</form>
			<?php

			$woo_recipes = array();
			foreach ( $recipes as $slug => $recipe ) {
				if ( ! empty( $recipe['woo_page'] ) ) {
					$woo_recipes[ $slug ] = $recipe;
				}
			}
			if ( $woo_recipes && Lyra_Woo::is_active() ) {
				echo '<h3>' . esc_html__( 'WooCommerce pages', 'lyra-core' ) . '</h3>';
				echo '<p>' . esc_html__( 'Assigning replaces the page WooCommerce currently uses for cart/checkout.', 'lyra-core' ) . '</p>';
				foreach ( $woo_recipes as $slug => $recipe ) {
					?>
					<form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>" style="display:inline-block;margin-inline-end:8px">
						<input type="hidden" name="action" value="lyra_assign_woo" />
						<input type="hidden" name="slug" value="<?php echo esc_attr( $slug ); ?>" />
						<?php wp_nonce_field( 'lyra_assign_woo' ); ?>
						<button type="submit" class="button"><?php echo esc_html( sprintf( /* translators: %s: woo page type */ __( 'Assign as WooCommerce %s page', 'lyra-core' ), $recipe['woo_page'] ) ); ?></button>
					</form>
					<?php
				}
			}
		}

		?>
		<h2><?php esc_html_e( 'Options', 'lyra-core' ); ?></h2>
		<form method="post" action="options.php">
			<?php settings_fields( 'lyra_core' ); ?>
			<p><label><input type="checkbox" name="lyra_settings[persian_digits]" value="1" <?php checked( 1, (int) lyra_option( 'persian_digits', 0 ) ); ?> /> <?php esc_html_e( 'Show Persian digits (lyra_digits helper)', 'lyra-core' ); ?></label></p>
			<p><label><input type="checkbox" name="lyra_settings[delete_on_uninstall]" value="1" <?php checked( 1, (int) lyra_option( 'delete_on_uninstall', 0 ) ); ?> /> <?php esc_html_e( 'Delete generated pages/templates when the plugin is uninstalled', 'lyra-core' ); ?></label></p>
			<?php submit_button(); ?>
		</form>

		<h2><?php esc_html_e( 'Log', 'lyra-core' ); ?></h2>
		<?php
		$log = lyra_get_log();
		if ( empty( $log ) ) {
			echo '<p>' . esc_html__( 'Nothing logged yet.', 'lyra-core' ) . '</p>';
		} else {
			echo '<table class="widefat striped" style="max-width:900px"><tbody>';
			foreach ( array_slice( $log, 0, 20 ) as $line ) {
				printf(
					'<tr><td style="width:160px">%1$s</td><td>%2$s</td></tr>',
					esc_html( isset( $line['time'] ) ? $line['time'] : '' ),
					esc_html( isset( $line['message'] ) ? $line['message'] : '' )
				);
			}
			echo '</tbody></table>';
		}
		echo '</div>';
	}
}
