<?php
/**
 * Plausible Analytics | Admin Filters.
 *
 * @since 1.0.0
 *
 * @package    WordPress
 * @subpackage Plausible Analytics
 */

namespace Plausible\Analytics\WP\Admin;

// Bailout, if accessed directly.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class Filters {

	/**
	 * Constructor.
	 *
	 * @return void
	 * @since 1.0.0
	 *
	 */
	public function __construct() {
		add_filter( 'admin_footer_text', [ $this, 'add_admin_footer_text' ] );
		add_filter( 'plugin_action_links_' . PLAUSIBLE_ANALYTICS_PLUGIN_BASENAME, [
			$this,
			'add_plugin_action_links'
		] );
	}

	/**
	 * Add rating links to the admin dashboard.
	 *
	 * @param string $footer_text The existing footer text.
	 *
	 * @return string
	 * @since 1.0.0
	 *
	 */
	public function add_admin_footer_text( $footerText ) {
		$current_screen = get_current_screen();

		if ( true == stristr( $current_screen->base, 'plausible-analytics' ) ) {
			$ratingText = sprintf(
			/* translators: %s: Link to 5 star rating */
				__( 'If you like <strong>Plausible Analytics</strong> please leave us a %s rating. It takes a minute and helps a lot. Thanks in advance!', 'plausible-analytics' ),
				'<a href="https://wordpress.org/support/view/plugin-reviews/plausible-analytics?filter=5#postform" target="_blank" class="plausible-analytics-rating-link" style="text-decoration:none;" data-rated="' . esc_attr__( 'Thanks :)', 'plausible-analytics' ) . '">&#9733;&#9733;&#9733;&#9733;&#9733;</a>'
			);

			return $ratingText;
		} else {
			return $footerText;
		}
	}

	/**
	 * Plugin page action links.
	 *
	 * @param array $actions An array of plugin action links.
	 *
	 * @return array
	 * @since 1.0.0
	 *
	 */
	public function add_plugin_action_links( $actions ) {
		$new_actions = [
			'settings' => sprintf(
				'<a href="%1$s">%2$s</a>',
				admin_url( 'admin.php?page=plausible-analytics' ),
				esc_html__( 'Settings', 'plausible-analytics' )
			),
			'support'  => sprintf(
				'<a target="_blank" href="%1$s">%2$s</a>',
				esc_url_raw( 'https://wordpress.org/support/plugin/plausible-analytics/' ),
				esc_html__( 'Support', 'plausible-analytics' )
			),
		];

		return array_merge( $new_actions, $actions );
	}
}
