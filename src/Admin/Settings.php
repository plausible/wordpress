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
		add_dashboard_page(
			esc_html__( 'Analytics', 'plausible-analytics' ),
			esc_html__( 'Analytics', 'plausible-analytics' ),
			'manage_options',
			'plausible-analytics-statistics',
			[ $this, 'statistics_page' ]
		);
		add_options_page(
			esc_html__( 'Plausible Analytics', 'plausible-analytics' ),
			esc_html__( 'Plausible Analytics', 'plausible-analytics' ),
			'manage_options',
			'plausible-analytics',
			[ $this, 'plausible_analytics_settings_page' ]
		);
	}

	/**
	 * Get Admin Header.
	 *
	 * @param string $name Header Name.
	 *
	 * @since  1.2.0
	 * @access public
	 *
	 * @return mixed
	 */
	public function get_header( $name ) {
		?>
		<div class="plausible-analytics-header">
			<div class="plausible-analytics-logo">
				<img src="<?php echo PLAUSIBLE_ANALYTICS_PLUGIN_URL . '/assets/dist/images/icon.png'; ?>" alt="<?php esc_html_e( 'Plausible Analytics', 'plausible-analytics' ); ?>" />
			</div>
			<div class="plausible-analytics-title">
				<h1><?php echo $name; ?></h1>
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
		<?php
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
		$shared_link          = ! empty( $settings['shared_link'] ) ? $settings['shared_link'] : "https://plausible.io/share/{$domain}?auth=XXXXXXXXXXXX";

		echo $this->get_header( esc_html__( 'Settings', 'plausible-analytics' ) );
		?>
		<div class="wrap plausible-analytics-wrap">
			<form id="plausible-analytics-settings-form" class="plausible-analytics-form">
				<div class="plausible-analytics-admin-field plausible-analytics-admin-menu">
					<ul class="plausible-analytics-admin-tabs">
						<li>
							<a href="#" class="active" data-tab="general">
								<?php esc_html_e( 'General', 'plausible-analytics' ); ?>
							</a>
						</li>
						<li>
							<a href="#" data-tab="self-hosted">
								<?php esc_html_e( 'Self Hosted', 'plausible-analytics' ); ?>
							</a>
						</li>
					</ul>
				</div>
				<div id="plausible-analytics-content-general" class="plausible-analytics-content plausible-analytics-show">
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
							<label for="embed-analytics">
								<?php esc_html_e( 'View your stats in your WordPress dashboard', 'plausible-analytics' ); ?>
							</label>
							<?php echo Helpers::display_toggle_switch( 'embed_analytics' ); ?>
						</div>
						<div class="plausible-analytics-admin-field-content">
							<label>
								<?php esc_html_e( 'Shared Link:', 'plausible-analytics' ); ?>
								<span class="plausible-analytics-admin-field-input">
									<input style="min-width: 550px;" type="text" name="plausible_analytics_settings[shared_link]" value="<?php echo $shared_link; ?>" />
								</span>
							</label>
						</div>
						<div class="plausible-analytics-description">
							<?php
							echo sprintf(
								'<ol><li>%1$s <a href="%2$s" target="_blank">%3$s</a></li><li>%4$s</li><li>%5$s <a href="%6$s">%7$s</a></li></ol>',
								esc_html__( 'Create a secure and private shared link in your Plausible account.', 'plausible-analytics' ),
								esc_url( 'https://plausible.io/docs/shared-links' ),
								esc_html__( 'See how &raquo;', 'plausible-analytics' ),
								esc_html__( 'Enable this setting and paste your shared link to view your stats in your WordPress dashboard.', 'plausible-analytics' ),
								esc_html__( 'View your site statistics within your WordPress Dashboard.', 'plausible-analytics' ),
								admin_url( 'index.php?page=plausible-analytics-statistics' ),
								esc_html__( 'View Statistics &raquo;', 'plausible-analytics' )
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
				</div>

				<div id="plausible-analytics-content-self-hosted" class="plausible-analytics-content">
					<div class="plausible-analytics-admin-field">
						<div class="plausible-analytics-admin-field-header">
							<label for="self-hosted-analytics">
								<?php esc_html_e( 'Self-hosted Plausible?', 'plausible-analytics' ); ?>
								<span class="plausible-analytics-admin-field-input">
									<input type="text" name="plausible_analytics_settings[self_hosted_domain]" value="<?php echo $self_hosted_domain; ?>"/>
								</span>
							</label>
							<?php echo Helpers::display_toggle_switch( 'is_self_hosted_analytics' ); ?>
						</div>
						<div class="plausible-analytics-description">
							<?php
							echo sprintf(
								'%1$s <a href="%2$s" target="_blank">%3$s</a>',
								esc_html__( 'If you\'re self-hosting Plausible on your own infrastructure, enter the domain name where you installed it to enable the integration with your self-hosted instance. Learn more', 'plausible-analytics' ),
								esc_url( 'https://plausible.io/self-hosted-web-analytics/' ),
								esc_html__( 'about Plausible Self-Hosted.', 'plausible-analytics' )
							);

							?>
						</div>
					</div>
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

	/**
	 * Statistics Page via Embed feature.
	 *
	 * @since  1.2.0
	 * @access public
	 *
	 * @return void
	 */
	public function statistics_page() {
		$settings            = Helpers::get_settings();
		$domain              = Helpers::get_domain();
		$can_embed_analytics = ! empty( $settings['embed_analytics'] ) ? $settings['embed_analytics'] : 'false';
		$shared_link         = ! empty( $settings['shared_link'] ) ?
			$settings['shared_link'] :
			'';

		// Display admin header.
		echo $this->get_header( esc_html__( 'Analytics', 'plausible-analytics' ) );

		if ( 'true' === $can_embed_analytics && ! empty( $shared_link ) ) {
			?>
			<iframe plausible-embed="" src="<?php echo "{$shared_link}&embed=true&theme=light&background=transparent"; ?>" scrolling="no" frameborder="0" loading="lazy" style="width: 100%; height: 1750px; "></iframe>
			<script async="" src="https://plausible.io/js/embed.host.js"></script>
			<?php
		} else {
			?>
			<div class="plausible-analytics-statistics-not-loaded">
				<?php
				echo sprintf(
					'%1$s <a href="%2$s">%3$s</a> %4$s %5$s <a href="%6$s">%7$s</a> %8$s',
					esc_html( 'Please', 'plausible-analytics' ),
					esc_url_raw( "https://plausible.io/{$domain}/settings/visibility" ),
					esc_html( 'click here', 'plausible-analytics' ),
					esc_html( 'to generate your shared link from your Plausible Analytics dashboard.', 'plausible-analytics' ),
					esc_html( 'Now, copy the generated shared link and', 'plausible-analytics' ),
					admin_url( 'options-general.php?page=plausible-analytics' ),
					esc_html( 'paste here', 'plausible-analytics' ),
					esc_html( 'under Embed Analytics to view Plausible Analytics dashboard within your WordPress site.', 'plausible-analytics' )
				);
				?>
			</p>
			<?php
		}
	}
}
