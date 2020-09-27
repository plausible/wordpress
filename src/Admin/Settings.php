<?php
/**
 * Plausible Analytics | Settings.
 *
 * @since 1.0.0
 *
 * @package    WordPress
 * @subpackage Plausible Analytics
 */

namespace Plausible\Analytics\WP\Admin;

use Plausible\Analytics\WP\Includes\Helpers;

// Bailout, if accessed directly.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class Settings {

	/**
	 * Constructor.
	 *
	 * @since  1.0.0
	 * @access public
	 *
	 * @return void
	 */
	public function __construct() {
		add_action( 'admin_menu', [ $this, 'register_menu' ] );
	}

	/**
	 * Register Menu.
	 *
	 * @since  1.0.0
	 * @access public
	 *
	 * @return void
	 */
	public function register_menu() {
		add_options_page(
			esc_html__( 'Plausible Analytics', 'plausible-analytics' ),
			esc_html__( 'Plausible Analytics', 'plausible-analytics' ),
			'manage_options',
			'plausible-analytics',
			[ $this, 'plausible_analytics_settings_page' ]
		);
	}

	/**
	 * Settings Page.
	 *
	 * @since  1.0.0
	 * @access public
	 *
	 * @return void
	 */
	public function plausible_analytics_settings_page() {
		$domain = Helpers::get_domain();
		?>
		<div class="plausible-analytics-header">
			<div class="plausible-analytics-logo">
				<img src="<?php echo PLAUSIBLE_ANALYTICS_PLUGIN_URL . '/assets/dist/images/icon.png'; ?>" alt="<?php esc_html_e( 'Plausible Analytics', 'plausible-analytics' ); ?>" />
			</div>
			<div class="plausible-analytics-title">
				<h1><?php esc_html_e( 'Settings', 'plausible-analytics' ); ?></h1>
			</div>
			<div class="plausible-analytics-actions">
				<a class="plausible-analytics-btn" href="" target="_blank">
					<?php esc_html_e( 'Report a bug', 'plausible-analytics' ); ?>
				</a>
				<a class="plausible-analytics-btn" href="https://docs.plausible.io" target="_blank">
					<?php esc_html_e( 'Documentation', 'plausible-analytics' ); ?>
				</a>
			</div>
		</div>
		<div class="wrap plausible-analytics-wrap">
			<form id="plausible-analytics-settings-form" class="plausible-analytics-form" method="POST">
				<div class="plausible-analytics-admin-field">
					<div class="plausible-analytics-admin-field-header">
						<label for="domain-connected">
							<?php esc_html_e( 'Domain', 'plausible-analytics' ); ?>
						</label>
						<div>
							<?php echo $domain; ?>
							<a class="plausible-analytics-link" href="<?php echo Helpers::get_analytics_dashboard_url(); ?>" target="_blank">
								<?php esc_html_e( 'Open Analytics', 'plausible-analytics' ); ?>
							</a>
						</div>
					</div>
					<p class="plausible-analytics-description">
						<?php esc_html_e( 'We have fetched the domain name for which Plausible Analytics will be used. We assume that you have already setup the domain on our website.', 'plausible-analytics' ); ?>
					</p>
				</div>
				<div class="plausible-analytics-admin-field">
					<div class="plausible-analytics-admin-field-header">
						<label for="custom-domain">
							<?php esc_html_e( 'Custom Domain', 'plausible-analytics' ); ?>
						</label>
						<?php echo Helpers::display_toggle_switch( 'custom_domain' ); ?>
					</div>
					<p class="plausible-analytics-description">
						<?php
						echo sprintf(
							'%1$s %2$s %3$s %4$s %5$s',
							esc_html__( 'Enable this setting and configure it to link with Plausible Analytics on your custom domain.', 'plausible-analytics' ),
							__( 'For example,', 'plausible-analytics' ),
							"<code>stats.$domain</code>",
							__( 'or', 'plausible-analytics' ),
							"<code>analytics.$domain</code>"
						);

						?>
					</p>
				</div>
				<div class="plausible-analytics-admin-field">
					<div class="plausible-analytics-admin-field-header">
						<label for="track-administrator">
							<?php esc_html_e( 'Track analytics for administrator', 'plausible-analytics' ); ?>
						</label>
						<?php echo Helpers::display_toggle_switch( 'track_administrator' ); ?>
					</div>
					<p class="plausible-analytics-description">
						<?php esc_html_e( 'By default, we won\'t be tracking analytics for administrator. If you want to track analytics for administrator then please enable this setting.', 'plausible-analytics' ); ?>
					</p>
				</div>
			</form>
		</div>
		<?php
	}
}
