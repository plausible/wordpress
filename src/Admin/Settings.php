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
		$settings             = Helpers::get_settings();
		$domain               = ! empty( $settings['domain_name'] ) ? $settings['domain_name'] : Helpers::get_domain();
		$custom_domain_prefix = ! empty( $settings['custom_domain_prefix'] ) ? $settings['custom_domain_prefix'] : 'analytics';
		$self_hosted_domain   = ! empty( $settings['self_hosted_domain'] ) ? $settings['self_hosted_domain'] : 'example.com';
		?>
		<div class="plausible-analytics-header">
			<div class="plausible-analytics-logo">
				<img src="<?php echo PLAUSIBLE_ANALYTICS_PLUGIN_URL . '/assets/dist/images/icon.png'; ?>" alt="<?php esc_html_e( 'Plausible Analytics', 'plausible-analytics' ); ?>" />
			</div>
			<div class="plausible-analytics-title">
				<h1><?php esc_html_e( 'Settings', 'plausible-analytics' ); ?></h1>
			</div>
			<div class="plausible-analytics-actions">
				<a class="plausible-analytics-btn" href="https://github.com/plausible/wordpress/issues/new" target="_blank">
					<?php esc_html_e( 'Report a bug', 'plausible-analytics' ); ?>
				</a>
				<a class="plausible-analytics-btn" href="https://docs.plausible.io" target="_blank">
					<?php esc_html_e( 'Documentation', 'plausible-analytics' ); ?>
				</a>
			</div>
		</div>
		<div class="wrap plausible-analytics-wrap">
			<form id="plausible-analytics-settings-form" class="plausible-analytics-form">
				<div class="plausible-analytics-admin-field">
					<div class="plausible-analytics-admin-field-header">
						<label for="domain-connected">
							<?php esc_html_e( 'Domain Name', 'plausible-analytics' ); ?>
							<span class="plausible-analytics-admin-field-input">
								<input type="text" name="plausible_analytics_settings[domain_name]" value="<?php echo $domain; ?>"/>
							</span>
						</label>
						<div>
							<a class="plausible-analytics-link" href="<?php echo Helpers::get_analytics_dashboard_url(); ?>" target="_blank">
								<?php esc_html_e( 'Open Analytics', 'plausible-analytics' ); ?>
							</a>
						</div>
					</div>
					<p class="plausible-analytics-description">
						<?php
						echo sprintf(
							'%1$s <a href="%2$s" target="_blank">%3$s</a> %4$s',
							esc_html__( 'We have fetched the domain name for which Plausible Analytics will be used. We assume that you have already setup the domain on our website.', 'plausible-analytics' ),
							esc_url( 'https://docs.plausible.io/register-account' ),
							esc_html__( 'Follow these instructions', 'plausible-analytics' ),
							esc_html__( 'to add your site to Plausible.', 'plausible-analytics' )
						);
						?>
					</p>
				</div>
				<div class="plausible-analytics-admin-field">
					<div class="plausible-analytics-admin-field-header">
						<label for="custom-domain">
							<?php esc_html_e( 'Custom Domain', 'plausible-analytics' ); ?>
							<span class="plausible-analytics-admin-field-input">
								<input type="text" name="plausible_analytics_settings[custom_domain_prefix]" value="<?php echo $custom_domain_prefix; ?>"/>
								<?php echo ".{$domain}"; ?>
							</span>
						</label>
						<?php echo Helpers::display_toggle_switch( 'custom_domain' ); ?>
					</div>
					<div class="plausible-analytics-description">
						<?php
						echo sprintf(
							'<ol><li>%1$s <a href="%2$s" target="_blank">%3$s</a></li><li>%4$s %5$s %6$s %7$s %8$s</li></ol>',
							esc_html__( 'Enable the custom domain functionality in your Plausible account.', 'plausible-analytics' ),
							esc_url( 'https://docs.plausible.io/custom-domain/' ),
							esc_html__( 'See how &raquo;', 'plausible-analytics' ),
							esc_html__( 'Enable this setting and configure it to link with Plausible Analytics on your custom domain.', 'plausible-analytics' ),
							__( 'For example,', 'plausible-analytics' ),
							"<code>stats.$domain</code>",
							__( 'or', 'plausible-analytics' ),
							"<code>analytics.$domain</code>"
						);

						?>
					</div>
				</div>
				<div class="plausible-analytics-admin-field">
					<div class="plausible-analytics-admin-field-header">
						<label for="self-hosted-analytics">
							<?php esc_html_e( 'Is Self Hosted Analytics?', 'plausible-analytics' ); ?>
							<span class="plausible-analytics-admin-field-input">
								<input type="text" name="plausible_analytics_settings[self_hosted_domain]" value="<?php echo $self_hosted_domain; ?>"/>
							</span>
						</label>
						<?php echo Helpers::display_toggle_switch( 'is_self_hosted_analytics' ); ?>
					</div>
					<div class="plausible-analytics-description">
						<?php
						echo sprintf(
							'<ol><li>%1$s <a href="%2$s" target="_blank">%3$s</a></li><li>%4$s %5$s %6$s %7$s %8$s</li></ol>',
							esc_html__( 'Enable the custom domain functionality in your Plausible account.', 'plausible-analytics' ),
							esc_url( 'https://docs.plausible.io/custom-domain/' ),
							esc_html__( 'See how &raquo;', 'plausible-analytics' ),
							esc_html__( 'Enable this setting and configure it to link with Plausible Analytics on your custom domain.', 'plausible-analytics' ),
							__( 'For example,', 'plausible-analytics' ),
							"<code>stats.$domain</code>",
							__( 'or', 'plausible-analytics' ),
							"<code>analytics.$domain</code>"
						);

						?>
					</div>
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
				<div class="plausible-analytics-admin-field">
					<div class="plausible-analytics-admin-field-header">
						<button
							id="plausible-analytics-save-btn"
							class="plausible-analytics-btn plausible-analytics-save-btn"
							data-default-text="<?php esc_html_e( 'Save Changes', 'plausible-analytics' ); ?>"
							data-saved-text="<?php esc_html_e( 'Saved!', 'plausible-analytics' ); ?>"
						>
							<span><?php esc_html_e( 'Save Changes', 'plausible-analytics' ); ?></span>
							<span class="plausible-analytics-spinner">
								<div class="plausible-analytics-spinner--bounce-1"></div>
								<div class="plausible-analytics-spinner--bounce-2"></div>
							</span>
						</button>
						<input class="plausible-analytics-admin-settings-roadblock" type="hidden" name="plausible_analytics_settings[roadblock]" value="<?php echo wp_create_nonce( 'plausible-analytics-settings-roadblock' ); ?>"/>
					</div>
				</div>
			</form>
		</div>
		<?php
	}
}
