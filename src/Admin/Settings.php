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
	 * @return void
	 * @since  1.0.0
	 * @access public
	 *
	 */
	public function __construct() {
		add_action( 'admin_menu', [ $this, 'register_menu' ] );
	}

	/**
	 * Register Menu.
	 *
	 * @return void
	 * @since  1.0.0
	 * @access public
	 *
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
	 * Settings Page.
	 *
	 * @return void
	 * @since  1.0.0
	 * @access public
	 *
	 */
	public function plausible_analytics_settings_page() {

		$settings                  = Helpers::get_settings();
		$is_default_settings_saved = Helpers::is_default_settings_saved();
		$is_js_files_created       = Helpers::is_js_files_created();
		$plugin_version_from_db    = Helpers::get_plugin_version_from_db();

		$debug_info = $settings
					  + [ 'is_default_settings_saved' => $is_default_settings_saved ]
					  + [ 'is_js_files_created' => $is_js_files_created ]
					  + [ 'plugin_version_from_db' => $plugin_version_from_db ];

		$domain                   = ! empty( $settings['domain_name'] ) ? esc_attr( $settings['domain_name'] ) : Helpers::get_domain();
		$self_hosted_domain       = ! empty( $settings['self_hosted_domain'] ) ? esc_attr( $settings['self_hosted_domain'] ) : '';
		$is_self_hosted_analytics = ! empty( $self_hosted_domain ) && isset( $settings['is_self_hosted_analytics'] ) && $settings['is_self_hosted_analytics'] === 'true';
		$script_path              = ! empty( $settings['script_path'] ) ? trailingslashit( $settings['script_path'] ) : '';
		$event_path               = ! empty( $settings['event_path'] ) ? trailingslashit( $settings['event_path'] ) : '';
		$is_proxy                 = isset( $settings['is_proxy'] ) && $settings['is_proxy'] === 'true';
		$is_custom_path           = $is_proxy && ! empty( $script_path ) && ! empty( $event_path ) && isset( $settings['is_custom_path'] ) && $settings['is_custom_path'] === 'true';
		$embed_analytics          = isset( $settings['embed_analytics'] ) && $settings['embed_analytics'] === 'true';
		$shared_link              = ! empty( $settings['shared_link'] ) ? esc_url( $settings['shared_link'] ) : "https://plausible.io/share/{$domain}?auth=XXXXXXXXXXXX";
		$track_administrator      = isset( $settings['track_administrator'] ) && $settings['track_administrator'] === 'true';

		echo $this->get_header( esc_html__( 'Settings', 'plausible-analytics' ) );
		?>
		<div class="wrap plausible-analytics-wrap">
			<div id="plausible-analytics-settings-errors" class="plausible-analytics-errors">
				<?php
				_e( sprintf( __( 'Your tracking script is running and collecting data, but we were unable to activate the feature that allows it to run from your domain name. %s', 'plausible-analytics' ), sprintf( '<a href="%s">%s</a>', esc_url( '#more-error-details' ), esc_html( __( 'See here for more details', 'plausible-analytics' ) ) ) ), 'plausible-analytics' );
				?>
				<ul></ul>
			</div>
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
						<?php if ( defined( 'WP_DEBUG' ) && true === WP_DEBUG ) : ?>
							<li>
								<a href="#" data-tab="info">
									<?php esc_html_e( 'Info', 'plausible-analytics' ); ?>
								</a>
							</li>
						<?php endif; ?>
					</ul>
				</div>
				<div id="plausible-analytics-content-general"
					 class="plausible-analytics-content plausible-analytics-show">
					<div class="plausible-analytics-admin-field">
						<div class="plausible-analytics-admin-field-header">
							<label for="domain-connected">
								<?php esc_html_e( 'Domain Name', 'plausible-analytics' ); ?>
								<span class="plausible-analytics-admin-field-input">
									<input pattern="([A-Za-z0-9]{1,50}\.)+[-A-Za-z0-9]{2,}" type="text"
										   name="plausible_analytics_settings[domain_name]"
										   placeholder="<?php esc_attr_e( $domain, 'plausible-analytics' ); ?>"
										   value="<?php esc_attr_e( $domain, 'plausible-analytics' ); ?>"/>
								</span>
							</label>
							<div>
								<a class="plausible-analytics-link"
								   href="<?php echo Helpers::get_analytics_dashboard_url(); ?>" target="_blank">
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
							<label for="is-proxy">
								<?php esc_html_e( 'Run the script as a first-party connection from your domain name', 'plausible-analytics' ); ?>
							</label>
							<?php echo Helpers::display_toggle_switch( 'is_proxy', $is_proxy ); ?>
						</div>
						<div class="plausible-analytics-description">

							<?php
							echo sprintf(
								'%1$s <a href="%2$s"  >%3$s</a> <br>',
								esc_html__(
									'This works out of the box and does not affect your server or loading time.
								Disable it if you want to run the script from the plausible.io domain name.
								You might see less accurate stats by disabling this due to adblockers blocking third-party scripts.',
									'plausible-analytics'
								),
								esc_url( 'https://plausible.io/docs/proxy/introduction' ),
								esc_html__( 'Read more here &raquo;', 'plausible-analytics' )
							);
							?>
							<br/><br/>
							<?php
							echo sprintf(
								'%1$s <a href="#advanced-proxy" >%2$s</a><div></div>',
								esc_html__( 'Optionally, you can enable a manually created proxy', 'plausible-analytics' ),
								esc_html__( 'here.', 'plausible-analytics' ),
								$this->get_proxy_server_software_help_html()
							);
							?>

						</div>
						<div id="advanced-proxy"
							 class="<?php esc_attr_e( ! $is_custom_path ? 'plausible-analytics-hidden' : '', 'plausible-analytics' ); ?>">
							<div class="plausible-analytics-admin-field-content">
								<div class="plausible-analytics-admin-field-sub-header">
									<label for="is_custom_path">
										<?php esc_html_e( 'Run analytics script from a custom path', 'plausible-analytics' ); ?>
									</label>
									<?php Helpers::display_toggle_switch( 'is_custom_path', $is_custom_path ); ?>
								</div>
								<div class="plausible-analytics-sub-description">
									<?php esc_html_e( 'Our default proxy works out of the box and is an excellent solution for most sites, but if you want to specify a custom proxy that you have created manually, you can do so here', 'plausible-analytics' ); ?>
								</div>
								<label>
									<?php esc_html_e( 'Script Path:', 'plausible-analytics' ); ?>
									<span class="plausible-analytics-admin-field-input">
										<?php
										echo sprintf(
											'<input placeholder="%1$s%2$s" style="%3$s;" type="%4$s" name="%5$s" value="%6$s" %7$s />',
											esc_html( 'https://' . $domain ),
											esc_html__( '/stats/js/', 'plausible-analytics' ),
											esc_attr( 'width: 550px; max-width: 100%;' ),
											esc_attr( 'url' ),
											esc_attr( 'plausible_analytics_settings[script_path]' ),
											esc_url( $script_path ),
											esc_attr( ! $is_custom_path ? 'disabled' : '' )
										);
										?>
									</span>
								</label>
								<br/>
								<label>
									<?php esc_html_e( 'Event API Path:', 'plausible-analytics' ); ?>
									<span class="plausible-analytics-admin-field-input">
										<?php
										echo sprintf(
											'<input placeholder="%1$s%2$s" style="%3$s;" type="%4$s" name="%5$s" value="%6$s" %7$s />',
											esc_html( 'https://' . $domain ),
											esc_html__( '/stats/api/', 'plausible-analytics' ),
											esc_attr( 'width: 550px; max-width: 100%;' ),
											esc_attr( 'url' ),
											esc_attr( 'plausible_analytics_settings[event_path]' ),
											esc_url( $event_path ),
											esc_attr( ! $is_custom_path ? 'disabled' : '' )
										);
										?>
									</span>
								</label>
							</div>
							<div class="plausible-analytics-description">
								<?php
								echo sprintf(
									'%1$s <a href="%2$s" target="_blank">%3$s</a> <br> %4$s',
									esc_html__( 'You can also setup the proxy in your own infrastructure.', 'plausible-analytics' ),
									esc_url( 'https://plausible.io/docs/proxy/introduction' ),
									esc_html__( 'See how &raquo;', 'plausible-analytics' ),
									$this->get_proxy_server_software_help_html()
								);
								?>
							</div>
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
									<?php
									echo sprintf(
										'<input placeholder="%1$s" style="%2$s;" type="%3$s" name="%4$s" value="%5$s" %6$s />',
										esc_html( 'https://plausible.io/share/{$domain}?auth=XXXXXXXXXXXX' ),
										esc_attr( 'width: 550px; max-width: 100%;' ),
										esc_attr( 'url' ),
										esc_attr( 'plausible_analytics_settings[shared_link]' ),
										esc_url( $shared_link ),
										esc_attr( ! $embed_analytics ? 'disabled' : '' )
									);
									?>
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
									<input pattern="([A-Za-z0-9]{1,50}\.)+[-A-Za-z0-9]{2,}" type="text"
										   name="plausible_analytics_settings[self_hosted_domain]"
										   placeholder="<?php esc_attr_e( $self_hosted_domain, 'plausible-analytics' ); ?>"
										   value="<?php esc_attr_e( $self_hosted_domain, 'plausible-analytics' ); ?>"/>
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

				<?php if ( defined( 'WP_DEBUG' ) && true === WP_DEBUG ) : ?>
					<div id="plausible-analytics-content-info" class="plausible-analytics-content">
						<div class="plausible-analytics-admin-field">
							<div class="plausible-analytics-admin-field-header">
								<label for="info-analytics">
									<?php esc_html_e( 'Debug Info', 'plausible-analytics' ); ?>
								</label>
							</div>
							<p class="plausible-analytics-description">
							<pre
								id="plausible-analytics-info-text"><?php print wp_json_encode( $debug_info, JSON_PRETTY_PRINT ); ?></pre>
							<br/>
							<a href="#"
							   title="<?php esc_html_e( 'Copy info to clipboard', 'plausible-analytics' ); ?>"
							   id="plausible-analytics-info-copy-btn"
							   class="plausible-analytics-btn plausible-analytics-info-copy-btn"
							   data-copied-text="<?php esc_html_e( 'Copied!', 'plausible-analytics' ); ?>"
							>
								<span><?php esc_html_e( 'Copy Info', 'plausible-analytics' ); ?></span>
							</a>
							</p>
						</div>
					</div>
				<?php endif; ?>

				<div class="plausible-analytics-admin-field">
					<div class="plausible-analytics-admin-field-header">
						<button
							id="plausible-analytics-save-btn"
							class="plausible-analytics-btn plausible-analytics-save-btn"
							data-default-text="<?php esc_html_e( 'Save Changes', 'plausible-analytics' ); ?>"
							data-saved-text="<?php esc_html_e( 'Saved!', 'plausible-analytics' ); ?>"
							data-saved-error="<?php esc_html_e( 'Something Went Wrong!', 'plausible-analytics' ); ?>"
						>
							<span><?php esc_html_e( 'Save Changes', 'plausible-analytics' ); ?></span>
							<span class="plausible-analytics-spinner">
								<div class="plausible-analytics-spinner--bounce-1"></div>
								<div class="plausible-analytics-spinner--bounce-2"></div>
							</span>
						</button>
						<input class="plausible-analytics-admin-settings-roadblock" type="hidden"
							   name="plausible_analytics_settings[roadblock]"
							   value="<?php echo wp_create_nonce( 'plausible-analytics-settings-roadblock' ); ?>"/>
					</div>
				</div>
			</form>
		</div>
		<?php
	}

	/**
	 * Get Admin Header.
	 *
	 * @param string $name Header Name.
	 *
	 * @return mixed
	 * @since  1.2.0
	 * @access public
	 *
	 */
	public function get_header( $name ) {
		?>
		<div class="plausible-analytics-header">
			<div class="plausible-analytics-logo">
				<img
					src="<?php echo trailingslashit( esc_url( PLAUSIBLE_ANALYTICS_PLUGIN_URL ) ) . 'assets/dist/images/icon.png'; ?>"
					alt="<?php esc_html_e( 'Plausible Analytics', 'plausible-analytics' ); ?>"/>
			</div>
			<div class="plausible-analytics-header-content">
				<div class="plausible-analytics-title">
					<h1><?php echo esc_html( $name ); ?></h1>
				</div>
				<div class="plausible-analytics-actions">
					<a class="plausible-analytics-btn"
					   href="<?php echo esc_url( 'https://github.com/plausible/wordpress/issues/new' ); ?>"
					   target="_blank">
						<?php esc_html_e( 'Report a bug', 'plausible-analytics' ); ?>
					</a>
					<a class="plausible-analytics-btn" href="<?php echo esc_url( 'https://docs.plausible.io' ); ?>"
					   target="_blank">
						<?php esc_html_e( 'Documentation', 'plausible-analytics' ); ?>
					</a>
				</div>
			</div>
		</div>
		<?php
	}

	/**
	 * Return proxy help HTML.
	 *
	 * @return String
	 * @since  1.2.5
	 * @access public
	 *
	 */
	public function get_proxy_server_software_help_html() {
		$server_software = Helpers::get_server_software();

		$html = '';

		if ( $server_software ) {

			if ( 'apache' == $server_software ) {
				$html = sprintf(
					'%1$s <a href="%2$s" target="_blank">%3$s</a>',
					esc_html__( "It looks like you're using The Apache HTTP Server. ", 'plausible-analytics' ),
					esc_url( 'https://github.com/Neoflow/ReverseProxy-PlausibleAnalytics' ),
					esc_html__( 'See the documentation for setting up your custom proxy in Apache HTTP Server &raquo;', 'plausible-analytics' )
				);
			}

			if ( 'nginx' == $server_software ) {
				$html = sprintf(
					'%1$s <a href="%2$s" target="_blank">%3$s</a>',
					esc_html__( "It looks like you're using Nginx. ", 'plausible-analytics' ),
					esc_url( 'https://plausible.io/docs/proxy/guides/nginx' ),
					esc_html__( 'See the documentation for setting up your custom proxy in Nginx &raquo;', 'plausible-analytics' )
				);
			}

			if ( 'cloudflare' == $server_software ) {
				$html = sprintf(
					'%1$s <a href="%2$s" target="_blank">%3$s</a>',
					esc_html__( "It looks like you're using Cloudflare CDN/Proxy. ", 'plausible-analytics' ),
					esc_url( 'https://plausible.io/docs/proxy/guides/cloudflare' ),
					esc_html__( 'See the documentation for setting up your custom proxy in Cloudflare CDN/Proxy &raquo;', 'plausible-analytics' )
				);
			}

			if ( 'cloudfront' == $server_software ) {
				$html = sprintf(
					'%1$s <a href="%2$s" target="_blank">%3$s</a>',
					esc_html__( "It looks like you're using Amazon CloudFront. ", 'plausible-analytics' ),
					esc_url( 'https://plausible.io/docs/proxy/guides/cloudfront' ),
					esc_html__( 'See the documentation for setting up your custom proxy in Amazon CloudFront &raquo;', 'plausible-analytics' )
				);
			}
		}

		return $html;

	}

	/**
	 * Statistics Page via Embed feature.
	 *
	 * @return void
	 * @since  1.2.0
	 * @access public
	 *
	 */
	public function statistics_page() {
		$settings            = Helpers::get_settings();
		$domain              = Helpers::get_domain();
		$can_embed_analytics = ! empty( $settings['embed_analytics'] ) ? $settings['embed_analytics'] : 'false';
		$shared_link         = ! empty( $settings['shared_link'] ) ? $settings['shared_link'] : '';

		// Display admin header.
		echo $this->get_header( esc_html__( 'Analytics', 'plausible-analytics' ) );

		if ( 'true' === $can_embed_analytics && ! empty( $shared_link ) ) {
			?>
			<iframe plausible-embed=""
					src="<?php echo esc_url( $shared_link ) . '&embed=true&theme=light&background=transparent'; ?>"
					scrolling="no" frameborder="0" loading="lazy" style="width: 100%; height: 1750px; "></iframe>
			<script async="" src="https://plausible.io/js/embed.host.js"></script>
			<?php
		} else {
			?>
			<div class="plausible-analytics-statistics-not-loaded">
				<?php
				echo sprintf(
					'%1$s <a href="%2$s">%3$s</a> %4$s %5$s <a href="%6$s">%7$s</a> %8$s',
					esc_html( 'Please', 'plausible-analytics' ),
					esc_url( "https://plausible.io/{$domain}/settings/visibility" ),
					esc_html( 'click here', 'plausible-analytics' ),
					esc_html( 'to generate your shared link from your Plausible Analytics dashboard.', 'plausible-analytics' ),
					esc_html( 'Now, copy the generated shared link and', 'plausible-analytics' ),
					esc_url( admin_url( 'options-general.php?page=plausible-analytics' ) ),
					esc_html( 'paste here', 'plausible-analytics' ),
					esc_html( 'under Embed Analytics to view Plausible Analytics dashboard within your WordPress site.', 'plausible-analytics' )
				);
				?>
			</div>
			<?php
		}
	}

}
