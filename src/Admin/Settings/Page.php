<?php
/**
 * Plausible Analytics | Settings API.
 *
 * @since      1.3.0
 *
 * @package    WordPress
 * @subpackage Plausible Analytics
 */

namespace Plausible\Analytics\WP\Admin\Settings;

use Plausible\Analytics\WP\Includes\Helpers;

// Bailout, if accessed directly.
if ( ! defined( 'ABSPATH' ) ) {
	wp_die( 'Cheat\'in huh?' );
}

class Page extends API {

	/**
	 * Constructor.
	 *
	 * @since  1.3.0
	 * @access public
	 *
	 * @return void
	 */
	public function __construct() {
		$settings           = Helpers::get_settings();
		$domain             = ! empty( $settings['domain_name'] ) ? $settings['domain_name'] : Helpers::get_domain();
		$self_hosted_domain = defined( 'PLAUSIBLE_SELF_HOSTED_DOMAIN' ) ? PLAUSIBLE_SELF_HOSTED_DOMAIN : ( ! empty( $settings['self_hosted_domain'] ) ? $settings['self_hosted_domain'] : '' );
		$shared_link        = ! empty( $settings['shared_link'] ) ? $settings['shared_link'] : '';
		$excluded_pages     = ! empty( $settings['excluded_pages'] ) ? $settings['excluded_pages'] : '';

		$this->fields = [
			'general'     => [
				[
					'label'  => esc_html__( 'Connect your website with Plausible Analytics', 'plausible-analytics' ),
					'slug'   => 'connect_to_plausible_analytics',
					'type'   => 'group',
					'desc'   => sprintf(
						'%1$s <a href="%2$s" target="_blank">%3$s</a> %4$s',
						esc_html__( 'We have fetched the domain name for which Plausible Analytics will be used. We assume that you have already setup the domain on our website.', 'plausible-analytics' ),
						esc_url( 'https://plausible.io/wordpress-analytics-plugin#how-to-get-started-with-plausible-analytics' ),
						esc_html__( 'Follow these instructions', 'plausible-analytics' ),
						esc_html__( 'to add your site to Plausible.', 'plausible-analytics' )
					),
					'toggle' => [
						'anchor' => 'https://plausible.io/' . $domain,
						'label'  => esc_html__( 'Open Analytics', 'plausible-analytics' ),
					],
					'fields' => [
						[
							'label' => esc_html__( 'Domain Name', 'plausible-analytics' ),
							'slug'  => 'domain_name',
							'type'  => 'text',
							'value' => $domain,
						],
					],
				],
				[
					'label'  => esc_html__( 'Enhanced measurements', 'plausible-analytics' ),
					'slug'   => 'enhanced_measurements',
					'type'   => 'group',
					// translators: %1$s replaced with <code>outbound-links</code>.
					'desc'   => esc_html__( 'To complete the setup process of a particular enhanced measurement, click on the "Additional action required" link and follow the instructions.', 'plausible-analytics' ),
					'fields' => [
						'404'            => [
							'label'      => esc_html__( '404 error pages', 'plausible-analytics' ),
							'docs'       => 'https://plausible.io/wordpress-analytics-plugin#how-to-track-404-error-pages',
							'docs_label' => esc_html__( 'Additional action required', 'plausible-analytics' ),
							'slug'       => 'enhanced_measurements',
							'type'       => 'checkbox',
							'value'      => '404',
						],
						'outbound-links' => [
							'label'      => esc_html__( 'Outbound links', 'plausible-analytics' ),
							'docs'       => 'https://plausible.io/wordpress-analytics-plugin#how-to-track-external-link-clicks',
							'docs_label' => esc_html__( 'Additional action required', 'plausible-analytics' ),
							'slug'       => 'enhanced_measurements',
							'type'       => 'checkbox',
							'value'      => 'outbound-links',
						],
						'file-downloads' => [
							'label'      => esc_html__( 'File downloads', 'plausible-analytics' ),
							'docs'       => 'https://plausible.io/wordpress-analytics-plugin#how-to-track-file-downloads',
							'docs_label' => esc_html__( 'Additional action required', 'plausible-analytics' ),
							'slug'       => 'enhanced_measurements',
							'type'       => 'checkbox',
							'value'      => 'file-downloads',
						],
						'pageview-props' => [
							'label'      => esc_html__( 'Page types (as custom properties)', 'plausible-analytics' ),
							'docs'       => 'https://plausible.io/wordpress-analytics-plugin#how-to-track-page-types',
							'docs_label' => esc_html__( 'Additional action required', 'plausible-analytics' ),
							'slug'       => 'enhanced_measurements',
							'type'       => 'checkbox',
							'value'      => 'pageview-props',
						],
						'tagged-events'  => [
							'label'      => esc_html__( 'Custom events', 'plausible-analytics' ),
							'docs'       => 'https://plausible.io/wordpress-analytics-plugin#how-to-setup-custom-events-to-track-goal-conversions',
							'docs_label' => esc_html__( 'Additional action required', 'plausible-analytics' ),
							'slug'       => 'enhanced_measurements',
							'type'       => 'checkbox',
							'value'      => 'tagged-events',
						],
						'hash'           => [
							'label'      => esc_html__( 'Hash-based routing', 'plausible-analytics' ),
							'docs'       => 'https://plausible.io/wordpress-analytics-plugin#how-to-enable-hash-based-url-tracking',
							'docs_label' => esc_html__( 'Documentation', 'plausible-analytics' ),
							'slug'       => 'enhanced_measurements',
							'type'       => 'checkbox',
							'value'      => 'hash',
						],
						'compat'         => [
							'label'      => esc_html__( 'IE compatibility', 'plausible-analytics' ),
							'docs'       => 'https://plausible.io/wordpress-analytics-plugin#how-to-track-visitors-who-use-internet-explorer',
							'docs_label' => esc_html__( 'Documentation', 'plausible-analytics' ),
							'slug'       => 'enhanced_measurements',
							'type'       => 'checkbox',
							'value'      => 'compat',
						],
					],
				],
				[
					'label'  => esc_html__( 'Bypass ad blockers', 'plausible-analytics' ),
					'slug'   => 'bypass_ad_blockers',
					'type'   => 'group',
					'desc'   => sprintf(
						wp_kses(
							__( 'Concerned about ad blockers? You can run the Plausible script as a first-party connection from your domain name to count visitors who use ad blockers. The proxy uses WordPress\' API with a randomly generated endpoint, starting with <code>%1$s</code> and %2$s. <a href="%3$s" target="_blank">Learn more &raquo;</a>', 'plausible-analytics' ),
							wp_kses_allowed_html( 'post' )
						),
						get_site_url( null, rest_get_url_prefix() ),
						empty( Helpers::get_settings()['proxy_enabled'][0] ) ? 'a random directory/file for storing the JS file' : 'a JS file, called <code>' . str_replace( ABSPATH, '', Helpers::get_proxy_resource( 'cache_dir' ) . Helpers::get_proxy_resource( 'file_alias' ) . '.js</code>' ),
						'https://plausible.io/wordpress-analytics-plugin#how-to-enable-a-proxy-to-get-more-accurate-stats'
					),
					'toggle' => '',
					'fields' => [
						[
							'label'    => esc_html__( 'Enable proxy', 'plausible-analytics' ),
							'slug'     => 'proxy_enabled',
							'type'     => 'checkbox',
							'value'    => 'enable',
							'disabled' => ! empty( Helpers::get_settings()['self_hosted_domain'] ),
						],
						[
							'label' => '',
							'slug'  => 'proxy_warning',
							'type'  => 'hook',
						],
					],
				],
				[
					'label'  => esc_html__( 'View your stats in your WordPress dashboard', 'plausible-analytics' ),
					'slug'   => 'is_shared_link',
					'type'   => 'group',
					'desc'   => sprintf(
						'<ol><li>%1$s <a href="%2$s" target="_blank">%3$s</a></li><li>%4$s</li><li>%5$s <a href="%6$s">%7$s</a></li></ol>',
						esc_html__( 'Create a secure & private shared link in your Plausible account. Make sure the link is not password protected.', 'plausible-analytics' ),
						esc_url( 'https://plausible.io/wordpress-analytics-plugin#how-to-view-your-stats-directly-in-your-wordpress-dashboard' ),
						esc_html__( 'See how &raquo;', 'plausible-analytics' ),
						esc_html__( 'Enable this setting and paste your shared link to view your stats in your WordPress dashboard.', 'plausible-analytics' ),
						esc_html__( 'View your site statistics within your WordPress Dashboard.', 'plausible-analytics' ),
						admin_url( 'index.php?page=plausible_analytics_statistics' ),
						esc_html__( 'View Statistics &raquo;', 'plausible-analytics' )
					),
					'toggle' => '',
					'fields' => [
						[
							'label'       => esc_html__( 'Shared Link', 'plausible-analytics' ),
							'slug'        => 'shared_link',
							'type'        => 'text',
							'value'       => $shared_link,
							'placeholder' => esc_html__( 'E.g.', 'plausible-analytics' ) . "https://plausible.io/share/{$domain}?auth=XXXXXXXXXXXX",
						],
					],
				],
				[
					'label'  => esc_html__( 'Exclude specific pages from being tracked', 'plausible-analytics' ),
					'slug'   => 'is_exclude_pages',
					'type'   => 'group',
					'desc'   => sprintf(
						'%1$s <a href="%2$s" target="_blank">%3$s</a>',
						esc_html__( 'Exclude certain pages from being tracked. Wildcards are supported.', 'plausible-analytics' ),
						esc_url( 'https://plausible.io/wordpress-analytics-plugin#how-to-exclude-specific-pages-from-being-tracked' ),
						esc_html__( 'See syntax &raquo;', 'plausible-analytics' )
					),
					'toggle' => '',
					'fields' => [
						[
							'label'       => esc_html__( 'Excluded pages', 'plausible-analytics' ),
							'slug'        => 'excluded_pages',
							'type'        => 'textarea',
							'value'       => $excluded_pages,
							'placeholder' => esc_html__( 'E.g.', 'plausible-analytics' ) . '**hello-world**, /example-page/, *another-example-page',
						],
					],
				],
				[
					'label'  => esc_html__( 'Track analytics for user roles', 'plausible-analytics' ),
					'slug'   => 'can_role_tracked_user_roles',
					'type'   => 'group',
					'desc'   => esc_html__( 'By default, visits from logged in users aren\'t tracked. If you want to track visits for certain user roles then please specify them above.', 'plausible-analytics' ),
					'toggle' => false,
					'fields' => $this->build_user_roles_array( 'tracked_user_roles' ),
				],
				[
					'label'  => esc_html__( 'Show stats dashboard to additional user roles', 'plausible-analytics' ),
					'slug'   => 'can_access_analytics_page',
					'type'   => 'group',
					'desc'   => esc_html__( 'By default, the stats dashboard is only available to logged in administrators. If you want the dashboard to be available for other logged in users, then please specify them above.', 'plausible-analytics' ),
					'toggle' => false,
					'fields' => $this->build_user_roles_array( 'expand_dashboard_access', [ 'administrator' => true ] ),
				],
				[
					'label'         => esc_html__( 'Disable menu in toolbar', 'plausible-analytics' ),
					'slug'          => 'disable_toolbar_menu',
					'type'          => 'group',
					'desc'          => esc_html__( 'Check this option if you don\'t want the Plausible Analytics menu item to be added to the toolbar at the top of the screen.', 'plausible-analytics' ),
					'toggle'        => false,
					'add_sub_array' => false,
					'fields'        => [
						'disable_toolbar_menu' => [
							'label' => esc_html__( 'Disable toolbar menu', 'plausible-analytics' ),
							'slug'  => 'disable_toolbar_menu',
							'type'  => 'checkbox',
							'value' => '1',
						],
					],
				],
			],
			'self-hosted' => [
				[
					'label'  => esc_html__( 'Self-hosted Plausible Analytics', 'plausible-analytics' ),
					'slug'   => 'is_self_hosted',
					'type'   => 'group',
					'desc'   => sprintf(
						'%1$s <a href="%2$s" target="_blank">%3$s</a>',
						wp_kses( __( 'If you\'re self-hosting Plausible on your own infrastructure, enter the domain name where you installed it to enable the integration with your self-hosted instance. Multisites can use the <code>PLAUSIBLE_SELF_HOSTED_DOMAIN</code> constant to define the URL for all subsites at once.', 'plausible-analytics' ), 'post' ),
						esc_url( 'https://plausible.io/self-hosted-web-analytics/' ),
						esc_html__( 'Learn more about Plausible Self-Hosted.', 'plausible-analytics' )
					),
					'toggle' => '',
					'fields' => [
						[
							'label'       => esc_html__( 'Domain Name', 'plausible-analytics' ),
							'slug'        => 'self_hosted_domain',
							'type'        => 'text',
							'value'       => $self_hosted_domain,
							'placeholder' => 'e.g. ' . Helpers::get_domain(),
							'disabled'    => ! empty( Helpers::get_settings()['proxy_enabled'][0] ),
						],
						[
							'label' => '',
							'slug'  => 'self_hosted_domain_notice',
							'type'  => 'hook',
						],
					],
				],
			],
		];

		add_action( 'admin_menu', [ $this, 'register_menu' ] );
		add_action( 'in_admin_header', [ $this, 'render_page_header' ] );
		add_action( 'plausible_analytics_settings_proxy_warning', [ $this, 'render_proxy_warning' ] );
		add_action( 'plausible_analytics_settings_self_hosted_domain_notice', [ $this, 'maybe_render_self_hosted_warning' ] );
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
		$user       = wp_get_current_user();
		$user_roles = isset( $user->roles ) ? $user->roles : [];
		$is_allowed = false;
		/**
		 * By default, only allow administrators to access the Statistics page.
		 */
		$capabilities = 'manage_options';

		/**
		 * Let's see if current user is allowed to access the Stats page.
		 */
		foreach ( $user_roles as $user_role ) {
			if ( in_array( $user_role, Helpers::get_settings()['expand_dashboard_access'], true ) ) {
				$is_allowed = true;

				break;
			}
		}

		/**
		 * If current user role is allowed to access, overwrite $capabilities with this user's capabilities.
		 */
		if ( $is_allowed ) {
			if ( isset( $user->caps ) ) {
				reset( $user->caps );
				$capabilities = key( $user->caps );
			}
		}

		// Setup `Analytics` page under Dashboard.
		add_dashboard_page(
			esc_html__( 'Analytics', 'plausible-analytics' ),
			esc_html__( 'Analytics', 'plausible-analytics' ),
			$capabilities,
			'plausible_analytics_statistics',
			[ $this, 'statistics_page' ]
		);

		// Setup `Plausible Analytics` page under Settings.
		add_options_page(
			esc_html__( 'Plausible Analytics', 'plausible-analytics' ),
			esc_html__( 'Plausible Analytics', 'plausible-analytics' ),
			'manage_options',
			'plausible_analytics',
			[ $this, 'settings_page' ]
		);
	}

	/**
	 * Render Admin Page Header.
	 *
	 * @since  1.3.0
	 * @access public
	 *
	 * @return void
	 */
	public function render_page_header() {
		$screen = get_current_screen();

		// Bailout, if screen id doesn't match.
		if (
		! in_array(
			$screen->id,
			[
				'settings_page_plausible_analytics',
				'dashboard_page_plausible_analytics_statistics',
			],
			true
		)
		) {
			return;
		}
		?>
		<div class="plausible-analytics-header">
			<div class="plausible-analytics-logo">
				<img src="<?php echo PLAUSIBLE_ANALYTICS_PLUGIN_URL . '/assets/dist/images/icon.png'; ?>" alt="<?php esc_html_e( 'Plausible Analytics', 'plausible-analytics' ); ?>"/>
			</div>
			<div class="plausible-analytics-header-content">
				<div class="plausible-analytics-title">
					<h1><?php esc_html_e( 'Settings', 'plausible-analytics' ); ?></h1>
				</div>
		<?php $this->render_header_navigation(); ?>
			</div>
		</div>
		<?php
	}

	/**
	 * Render Header Navigation.
	 *
	 * @since  1.3.0
	 * @access public
	 *
	 * @return void
	 */
	public function render_header_navigation() {
		$screen = get_current_screen();

		// Bailout, if screen id doesn't match.
		if ( 'settings_page_plausible_analytics' !== $screen->id ) {
			return;
		}

		$current_tab = ! empty( $_GET['tab'] ) ? $_GET['tab'] : '';
		$tabs        = apply_filters(
			'plausible_analytics_settings_navigation_tabs',
			[
				'general'     => [
					'name'  => esc_html__( 'General', 'plausible-analytics' ),
					'url'   => admin_url( 'options-general.php?page=plausible_analytics' ),
					'class' => '' === $current_tab ? 'active' : '',
				],
				'self-hosted' => [
					'name'  => esc_html__( 'Self-Hosted', 'plausible-analytics' ),
					'url'   => admin_url( 'options-general.php?page=plausible_analytics&tab=self-hosted' ),
					'class' => 'self-hosted' === $current_tab ? 'active' : '',
				],
				// 'advanced'    => [
				//  'name'  => esc_html__( 'Advanced', 'plausible-analytics' ),
				//  'url'   => admin_url( 'options-general.php?page=plausible_analytics&tab=advanced' ),
				//  'class' => 'advanced' === $current_tab ? 'active' : '',
				// ],
			]
		);

		// Don't print any markup if we only have one tab.
		if ( count( $tabs ) === 1 ) {
			return;
		}
		?>
		<div class="plausible-analytics-header-navigation">
		<?php
		foreach ( $tabs as $tab ) {
			printf(
				'<a href="%1$s" class="%2$s">%3$s</a>',
				esc_url( $tab['url'] ),
				esc_attr( $tab['class'] ),
				esc_html( $tab['name'] )
			);
		}
		?>
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
		global $current_user;

		$settings               = Helpers::get_settings();
		$domain                 = Helpers::get_domain();
		$shared_link            = ! empty( $settings['shared_link'] ) ? $settings['shared_link'] : '';
		$has_access             = false;
		$user_roles_have_access = ! empty( $settings['expand_dashboard_access'] ) ? array_merge( [ 'administrator' ], $settings['expand_dashboard_access'] ) : [ 'administrator' ];

		foreach ( $current_user->roles as $role ) {
			if ( in_array( $role, $user_roles_have_access, true ) ) {
				$has_access = true;
			}
		}

			// Show error, if not having access.
		if ( ! $has_access ) :
			?>
			<div class="plausible-analytics-statistics-not-loaded">
			<?php
			echo sprintf(
				'%1$s',
				esc_html__( 'You don\'t have sufficient privileges to access the analytics dashboard. Please contact administrator of the website to grant you the access.', 'plausible-analytics' )
			);

			return;
			?>
				</div>
			<?php
		endif;

		/**
		 * Prior to this version, the default value would contain an example "auth" key, i.e. XXXXXXXXX.
		 *
		 * When this option was saved to the database, underlying code would fail, throwing a CORS related error in browsers.
		 *
		 * Now, we explicitly check for the existence of this example "auth" key, and display a human readable error message to
		 * those who haven't properly set it up.
		 *
		 * @since v1.2.5
		 */
		if ( ! empty( $shared_link ) || strpos( $shared_link, 'XXXXXX' ) !== false ) {
			$page_url = isset( $_GET['page-url'] ) ? esc_url( $_GET['page-url'] ) : '';

			// Append individual page URL if it exists.
			if ( $shared_link && $page_url ) {
				$shared_link .= "&page={$page_url}";
			}
			?>
			<iframe plausible-embed=""
					src="<?php echo "{$shared_link}&embed=true&theme=light&background=transparent"; ?>" scrolling="no"
					frameborder="0" loading="lazy" style="width: 100%; height: 1750px; "></iframe>
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
				esc_html( 'to generate your shared link from your Plausible Analytics dashboard. Make sure the link is not password protected.', 'plausible-analytics' ),
				esc_html( 'Now, copy the generated shared link and', 'plausible-analytics' ),
				admin_url( 'options-general.php?page=plausible_analytics' ),
				esc_html( 'paste here', 'plausible-analytics' ),
				esc_html( 'under Shared Link to view Plausible Analytics dashboard within your WordPress site.', 'plausible-analytics' )
			);
			?>
			</div>
			<?php
		}
	}

	/**
	 * Renders the warning for the Enable Proxy option.
	 *
	 * @since 1.3.0
	 *
	 * @return void
	 */
	public function render_proxy_warning() {
		if ( ! empty( Helpers::get_settings()['self_hosted_domain'] ) ) {
			echo wp_kses( __( 'This option is disabled, because the <strong>Domain Name</strong> setting is enabled under <em>Self-Hosted</em> settings.', 'plausible-analytics' ), 'post' );
		} else {
			echo sprintf( wp_kses( __( 'After enabling this option, please check your Plausible dashboard to make sure stats are being recorded. Are stats not being recorded? Do <a href="%s" target="_blank">reach out to us</a>. We\'re here to help!', 'plausible-analytics' ), 'post' ), 'https://plausible.io/contact' );
		}
	}

	/**
	 * Renders the Self-hosted warning if the Proxy is enabled.
	 *
	 * @since 1.3.3
	 *
	 * @return void
	 */
	public function maybe_render_self_hosted_warning() {
		if ( Helpers::proxy_enabled() ) {
			echo wp_kses( __( 'This option is disabled, because the <strong>Proxy</strong> setting is enabled under <em>General</em> settings.', 'plausible-analytics' ), 'post' );
		}
	}

	/**
	 * Load all available user roles as a list (sorted alphabetically) of checkboxes to be processed by the Settings API.
	 *
	 * @param string $slug
	 *
	 * @return array
	 */
	private function build_user_roles_array( $slug, $disable_elements = [] ) {
		$wp_roles = wp_roles()->roles ?? [];

		foreach ( $wp_roles as $id => $role ) {
			$roles_array[ $id ] = [
				'label' => $role['name'] ?? '',
				'slug'  => $slug,
				'type'  => 'checkbox',
				'value' => $id,
			];

			if ( in_array( $id, array_keys( $disable_elements ), true ) ) {
				$roles_array[ $id ]['disabled'] = true;

				if ( ! empty( $disable_elements[ $id ] ) ) {
					$roles_array[ $id ]['checked'] = $disable_elements[ $id ];
				}
			}
		}

		ksort( $roles_array, SORT_STRING );

		return $roles_array;
	}
}
