<?php
/**
 * Plausible Analytics | Admin
 */

namespace Plausible\Analytics\WP;

class AdminBar {
	/**
	 * Build class.
	 */
	public function __construct() {
		$this->init();
	}

	/**
	 * Action/filter hooks.
	 *
	 * @return void
	 */
	private function init() {
		add_action( 'admin_bar_menu', [ $this, 'admin_bar_node' ], 100 );
		add_filter( 'plausible_analytics_admin_bar_args', [ $this, 'maybe_add_analytics' ], 10, 2 );
		add_filter( 'plausible_analytics_admin_bar_args', [ $this, 'maybe_add_settings' ] );
	}


	/**
	 * Create admin bar nodes.
	 *
	 * @param \WP_Admin_Bar $admin_bar Admin bar object.
	 *
	 * @return void
	 * @since  1.3.0
	 */
	public function admin_bar_node( $admin_bar ) {
		$disable = ! empty( Helpers::get_settings()['disable_toolbar_menu'] );

		if ( $disable ) {
			return; // @codeCoverageIgnore
		}

		$settings     = Helpers::get_settings();
		$current_user = wp_get_current_user();

		$has_access             = false;
		$user_roles_have_access = array_merge(
			[ 'administrator' ],
			$settings['expand_dashboard_access'] ?? []
		);

		foreach ( $current_user->roles as $role ) {
			if ( in_array( $role, $user_roles_have_access, true ) ) {
				$has_access = true;
				break;
			}
		}

		if ( ! $has_access ) {
			return;
		}

		// Add main admin bar node.
		$args = apply_filters( 'plausible_analytics_admin_bar_args', [
			[
				'id'    => 'plausible-analytics',
				'title' => 'Plausible Analytics',
			]
		], $settings );

		foreach ( $args as $arg ) {
			$admin_bar->add_node( $arg );
		}
	}

	/**
	 * Adds the View Analytics link to the Admin Bar Menu if any of the related settings are enabled.
	 *
	 * @param $args
	 *
	 * @return mixed
	 */
	public function maybe_add_analytics( $args, $settings ) {
		if ( ! empty( $settings['enable_analytics_dashboard'] ) || ( ! empty( $settings['self_hosted_domain'] ) && ! empty( $settings['self_hosted_shared_link'] ) ) ) {
			$args[] = [
				'id'     => 'view-analytics',
				'title'  => esc_html__( 'View Analytics', 'plausible-analytics' ),
				'href'   => admin_url( 'index.php?page=plausible_analytics_statistics' ),
				'parent' => 'plausible-analytics',
			];

			// Add a link to individual page stats.
			if ( $this->is_singular() ) {
				global $post;

				$uri = wp_make_link_relative( get_permalink( $post->ID ) );

				$args[] = [
					'id'     => 'view-page-analytics',
					'title'  => esc_html__( 'View Page Analytics', 'plausible-analytics' ),
					'href'   => add_query_arg(
						'page-url',
						is_home() ? '' : $uri,
						admin_url( 'index.php?page=plausible_analytics_statistics' )
					),
					'parent' => 'plausible-analytics',
				];
			}
		}

		return $args;
	}

	/**
	 * This seam is merely to keep our code testable.
	 *
	 * @return bool
	 *
	 * @codeCoverageIgnore Because we don't want to test WP core functions.
	 */
	protected function is_singular() {
		return is_singular();
	}

	/**
	 * Adds the Settings link to the Admin Bar Menu if the current user can manage options.
	 *
	 * @param $args
	 *
	 * @return mixed
	 */
	public function maybe_add_settings( $args ) {
		if ( current_user_can( 'manage_options' ) ) {
			$args[] = [
				'id'     => 'settings',
				'title'  => esc_html__( 'Settings', 'plausible-analytics' ),
				'href'   => admin_url( 'options-general.php?page=plausible_analytics' ),
				'parent' => 'plausible-analytics',
			];
		}

		return $args;
	}
}
