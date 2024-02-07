<?php /** @noinspection HtmlUnknownTarget */

/**
 * Plausible Analytics | Settings API.
 * @since      1.3.0
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
	 * @var array|array[] $fields
	 */
	public $fields = [];

	/**
	 * Constructor.
	 * @since  1.3.0
	 * @access public
	 * @return void
	 */
	public function __construct() {
		$this->init();

		$settings           = Helpers::get_settings();
		$domain             = Helpers::get_domain();
		$self_hosted_domain = defined( 'PLAUSIBLE_SELF_HOSTED_DOMAIN' ) ? PLAUSIBLE_SELF_HOSTED_DOMAIN : $settings[ 'self_hosted_domain' ];

		$this->fields = [
			'general'     => [
				[
					'label'  => esc_html__( 'Connect your website with Plausible Analytics', 'plausible-analytics' ),
					'slug'   => 'connect_to_plausible_analytics',
					'type'   => 'group',
					'desc'   => sprintf(
						wp_kses(
							__(
								'Ensure your domain name matches the one in <a href="%s" target="_blank">your Plausible account</a>, then <a class="hover:cursor-pointer underline" id="plausible-create-api-token">generate the API token</a> (link opens in a new window) and paste it into the \'API token\' field.',
								'plausible-analytics'
							),
							'post'
						),
						'https://plausible.io/sites'
					),
					'fields' => [
						[
							'label' => esc_html__( 'Domain name', 'plausible-analytics' ),
							'slug'  => 'domain_name',
							'type'  => 'text',
							'value' => $domain,
						],
						[
							'label'    => esc_html__( 'API token', 'plausible-analytics' ),
							'slug'     => 'api_token',
							'type'     => 'text',
							'value'    => $settings[ 'api_token' ],
							'disabled' => ! empty( $settings[ 'self_hosted_domain' ] ),
						],
						[
							'label'    => empty( $settings[ 'domain_name' ] ) || empty( $settings[ 'api_token' ] ) ?
								esc_html__( 'Connect', 'plausible-analytics' ) : esc_html__( 'Connected', 'plausible-analytics' ),
							'slug'     => 'connect_plausible_analytics',
							'type'     => 'button',
							'disabled' => ( empty( $settings[ 'self_hosted_domain' ] ) ) &&
								( empty( $settings[ 'domain_name' ] ) || empty( $settings[ 'api_token' ] ) ) ||
								( ! empty( $settings[ 'domain_name' ] ) && ! empty( $settings[ 'api_token' ] ) ),
						],
					],
				],
				[
					'label'  => esc_html__( 'Enhanced measurements', 'plausible-analytics' ),
					'slug'   => 'enhanced_measurements',
					'type'   => 'group',
					// translators: %1$s replaced with <code>outbound-links</code>.
					'desc'   => esc_html__(
						'Enable enhanced measurements that you\'d like to track.',
						'plausible-analytics'
					),
					'fields' => [
						'404'            => [
							'label' => esc_html__( '404 error pages', 'plausible-analytics' ),
							'docs'  => 'https://plausible.io/wordpress-analytics-plugin#how-to-track-404-error-pages',
							'slug'  => 'enhanced_measurements',
							'type'  => 'checkbox',
							'value' => '404',
						],
						'outbound-links' => [
							'label' => esc_html__( 'Outbound links', 'plausible-analytics' ),
							'docs'  => 'https://plausible.io/wordpress-analytics-plugin#how-to-track-external-link-clicks',
							'slug'  => 'enhanced_measurements',
							'type'  => 'checkbox',
							'value' => 'outbound-links',
						],
						'file-downloads' => [
							'label' => esc_html__( 'File downloads', 'plausible-analytics' ),
							'docs'  => 'https://plausible.io/wordpress-analytics-plugin#how-to-track-file-downloads',
							'slug'  => 'enhanced_measurements',
							'type'  => 'checkbox',
							'value' => 'file-downloads',
						],
						'tagged-events'  => [
							'label' => esc_html__( 'Custom events', 'plausible-analytics' ),
							'docs'  => 'https://plausible.io/wordpress-analytics-plugin#how-to-setup-custom-events-to-track-goal-conversions',
							'slug'  => 'enhanced_measurements',
							'type'  => 'checkbox',
							'value' => 'tagged-events',
						],
						'revenue'        => [
							'label'    => esc_html__( 'Ecommerce revenue', 'plausible-analytics' ),
							'docs'     => 'https://plausible.io/wordpress-analytics-plugin#how-to-track-ecommerce-revenue',
							'slug'     => 'enhanced_measurements',
							'type'     => 'checkbox',
							'value'    => 'revenue',
							'disabled' => ! empty( $settings[ 'self_hosted_domain' ] ),
						],
						'pageview-props' => [
							'label' => esc_html__( 'Authors and categories', 'plausible-analytics' ),
							'docs'  => 'https://plausible.io/wordpress-analytics-plugin#how-to-send-custom-properties',
							'slug'  => 'enhanced_measurements',
							'type'  => 'checkbox',
							'value' => 'pageview-props',
						],
						'hash'           => [
							'label' => esc_html__( 'Hash-based routing', 'plausible-analytics' ),
							'docs'  => 'https://plausible.io/wordpress-analytics-plugin#how-to-enable-hash-based-url-tracking',
							'slug'  => 'enhanced_measurements',
							'type'  => 'checkbox',
							'value' => 'hash',
						],
						'compat'         => [
							'label' => esc_html__( 'IE compatibility', 'plausible-analytics' ),
							'docs'  => 'https://plausible.io/wordpress-analytics-plugin#how-to-track-visitors-who-use-internet-explorer',
							'slug'  => 'enhanced_measurements',
							'type'  => 'checkbox',
							'value' => 'compat',
						],
					],
				],
				[
					'label'  => esc_html__( 'Bypass ad blockers', 'plausible-analytics' ),
					'slug'   => 'bypass_ad_blockers',
					'type'   => 'group',
					'desc'   => sprintf(
						wp_kses(
							__(
								'Concerned about ad blockers? You can run the Plausible script as a first-party connection from your domain name to count visitors who use ad blockers. The proxy uses WordPress\' API with a randomly generated endpoint, starting with <code>%1$s</code> and %2$s. <a href="%3$s" target="_blank">Learn more &raquo;</a>',
								'plausible-analytics'
							),
							wp_kses_allowed_html( 'post' )
						),
						get_site_url( null, rest_get_url_prefix() ),
						empty(
						Helpers::get_settings()[ 'proxy_enabled' ]
						) ? 'a random directory/file for storing the JS file' : 'a JS file, called <code>' . str_replace(
								ABSPATH,
								'',
								Helpers::get_proxy_resource( 'cache_dir' ) . Helpers::get_proxy_resource(
									'file_alias'
								) . '.js</code>'
							),
						'https://plausible.io/wordpress-analytics-plugin#how-to-enable-a-proxy-to-get-more-accurate-stats'
					),
					'toggle' => '',
					'fields' => [
						[
							'label'    => esc_html__( 'Enable proxy', 'plausible-analytics' ),
							'slug'     => 'proxy_enabled',
							'type'     => 'checkbox',
							'value'    => 'on',
							'disabled' => ! empty( Helpers::get_settings()[ 'self_hosted_domain' ] ),
						],
					],
				],
				[
					'label'  => esc_html__( 'View your stats in your WordPress dashboard', 'plausible-analytics' ),
					'slug'   => 'is_shared_link',
					'type'   => 'group',
					'desc'   => esc_html__(
						'View your site statistics within your WordPress Dashboard.',
						'plausible-analytics'
					),
					'toggle' => '',
					'fields' => [
						[
							'label'    => esc_html__( 'View stats in WordPress', 'plausible-analytics' ),
							'slug'     => 'enable_analytics_dashboard',
							'type'     => 'checkbox',
							'value'    => 'on',
							'disabled' => empty( Helpers::get_settings()[ 'api_token' ] ) && empty( Helpers::get_settings()[ 'self_hosted_domain' ] ),
						],
					],
				],
				[
					'label'  => esc_html__( 'Exclude specific pages from being tracked', 'plausible-analytics' ),
					'slug'   => 'is_exclude_pages',
					'type'   => 'group',
					'desc'   => sprintf(
						'%1$s <a href="%2$s" target="_blank">%3$s</a>',
						esc_html__(
							'Exclude certain pages from being tracked. You can use an asterisk (*) to match patterns in your page URLs.',
							'plausible-analytics'
						),
						esc_url(
							'https://plausible.io/wordpress-analytics-plugin#how-to-exclude-specific-pages-from-being-tracked'
						),
						esc_html__( 'See syntax &raquo;', 'plausible-analytics' )
					),
					'toggle' => '',
					'fields' => [
						[
							'label'       => esc_html__( 'Excluded pages', 'plausible-analytics' ),
							'slug'        => 'excluded_pages',
							'type'        => 'textarea',
							'value'       => $settings[ 'excluded_pages' ],
							'placeholder' => esc_html__(
									'E.g.',
									'plausible-analytics'
								) . '/example-page/, *keyword*, /directory*',
						],
						[
							'label' => __( 'Save', 'plausible-analytics' ),
							'slug'  => 'save-excluded-pages',
							'type'  => 'button',
						],
					],
				],
				[
					'label'  => esc_html__( 'Track analytics for user roles', 'plausible-analytics' ),
					'slug'   => 'tracked_user_roles',
					'type'   => 'group',
					'desc'   => esc_html__(
						'By default, visits from logged in users aren\'t tracked. If you want to track visits for certain user roles then please specify them below.',
						'plausible-analytics'
					),
					'toggle' => false,
					'fields' => $this->build_user_roles_array( 'tracked_user_roles' ),
				],
				[
					'label'  => esc_html__( 'Show stats dashboard to additional user roles', 'plausible-analytics' ),
					'slug'   => 'expand_dashboard_access',
					'type'   => 'group',
					'desc'   => esc_html__(
						'By default, the stats dashboard is only available to logged in administrators. If you want the dashboard to be available for other logged in users, then please specify them below.',
						'plausible-analytics'
					),
					'toggle' => false,
					'fields' => $this->build_user_roles_array( 'expand_dashboard_access', [ 'administrator' => true ] ),
				],
				[
					'label'         => esc_html__( 'Disable menu in toolbar', 'plausible-analytics' ),
					'slug'          => 'disable_toolbar_menu',
					'type'          => 'group',
					'desc'          => esc_html__(
						'Check this option if you don\'t want the Plausible Analytics menu item to be added to the toolbar at the top of the screen.',
						'plausible-analytics'
					),
					'toggle'        => false,
					'add_sub_array' => false,
					'fields'        => [
						'disable_toolbar_menu' => [
							'label' => esc_html__( 'Disable toolbar menu', 'plausible-analytics' ),
							'slug'  => 'disable_toolbar_menu',
							'type'  => 'checkbox',
							'value' => 'on',
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
						wp_kses(
							__(
								'If you\'re self-hosting Plausible on your own infrastructure, enter the domain name where you installed it to enable the integration with your self-hosted instance. Multisites can use the <code>PLAUSIBLE_SELF_HOSTED_DOMAIN</code> constant to define the URL for all subsites at once.',
								'plausible-analytics'
							),
							'post'
						),
						esc_url( 'https://plausible.io/self-hosted-web-analytics/' ),
						esc_html__( 'Learn more about Plausible Self-Hosted.', 'plausible-analytics' )
					),
					'toggle' => '',
					'fields' => [
						[
							'label'       => esc_html__( 'Domain name', 'plausible-analytics' ),
							'slug'        => 'self_hosted_domain',
							'type'        => 'text',
							'value'       => $self_hosted_domain,
							'placeholder' => 'e.g. ' . Helpers::get_domain(),
							'disabled'    => Helpers::proxy_enabled(),
						],
						[
							'label'    => __( 'Save', 'plausible-analytics' ),
							'slug'     => 'save-self-hosted',
							'type'     => 'button',
							'disabled' => Helpers::proxy_enabled(),
						],
					],
				],
				[
					'label'  => esc_html__( 'View stats in your WordPress dashboard', 'plausible-analytics' ),
					'slug'   => 'self_hosted_shared_link',
					'type'   => 'group',
					'desc'   => sprintf(
						'<ol><li>' .
						__(
							'<a href="%s" target="_blank">Create a secure and private shared link</a> in your Plausible account.',
							'plausible-analytics'
						) .
						'<li>' .
						__( 'Paste the shared link in the text box to view your stats in your WordPress dashboard.', 'plausible-analytics' ) .
						'</li>' .
						'</li></ol>',
						esc_url( 'https://plausible.io/docs/embed-dashboard' )
					),
					'fields' => [
						[
							'label'       => esc_html__( 'Shared link', 'plausible-analytics' ),
							'slug'        => 'self_hosted_shared_link',
							'type'        => 'text',
							'value'       => $settings[ 'self_hosted_shared_link' ],
							'placeholder' => sprintf(
								wp_kses( __( 'E.g. https://plausible.io/share/%s?auth=XXXXXXXXXXXX', 'plausible-analytics' ), 'post' ),
								$domain
							),
							'disabled'    => Helpers::proxy_enabled(),
						],
						[
							'label'    => __( 'Save', 'plausible-analytics' ),
							'slug'     => 'save-self-hosted-shared-link',
							'type'     => 'button',
							'disabled' => Helpers::proxy_enabled(),
						],
					],
				],
			],
		];

		if ( ! empty( $settings[ 'self_hosted_domain' ] ) ) {
			$option_disabled_hook = [
				[
					'label' => '',
					'slug'  => 'option_disabled_by_self_hosted_domain',
					'type'  => 'hook',
				],
			];

			$fields = $this->fields[ 'general' ][ 0 ][ 'fields' ];

			array_splice( $fields, 2, 0, $option_disabled_hook );

			$this->fields[ 'general' ][ 0 ][ 'fields' ] = $fields;

			$fields = $this->fields[ 'general' ][ 1 ][ 'fields' ];

			array_splice( $fields, 5, 0, $option_disabled_hook );

			$this->fields[ 'general' ][ 1 ][ 'fields' ] = $fields;
		}

		if ( Helpers::proxy_enabled() || ! empty( $settings[ 'self_hosted_domain' ] ) ) {
			$this->fields[ 'general' ][ 2 ][ 'fields' ][] = [
				'label' => '',
				'slug'  => 'proxy_warning',
				'type'  => 'hook',
			];
		}

		/**
		 * If proxy is enabled, disable Self-hosted fields and display a warning.
		 */
		if ( Helpers::proxy_enabled() ) {
			$this->fields[ 'self-hosted' ][ 0 ][ 'fields' ][] = [
				'label' => '',
				'slug'  => 'option_disabled_by_proxy',
				'type'  => 'hook',
			];
			$this->fields[ 'self-hosted' ][ 1 ][ 'fields' ][] = [
				'label' => '',
				'slug'  => 'option_disabled_by_proxy',
				'type'  => 'hook',
			];
		}

		if ( empty( $settings[ 'api_token' ] ) && empty( $settings[ 'self_hosted_domain' ] ) ) {
			$this->fields[ 'general' ][ 0 ][ 'fields' ][] = [
				'label' => '',
				'slug'  => 'api_token_missing',
				'type'  => 'hook',
			];

			$this->fields[ 'general' ][ 3 ][ 'fields' ][] = [
				'label' => '',
				'slug'  => 'option_disabled_by_missing_api_token',
				'type'  => 'hook',
			];
		}

		/**
		 * If View Stats is enabled, display notice.
		 */
		if ( ! empty( $settings[ 'api_token' ] ) && ! empty( $settings[ 'enable_analytics_dashboard' ] ) ) {
			$this->fields[ 'general' ][ 3 ][ 'fields' ][] = [
				'label'     => '',
				'slug'      => 'enable_analytics_dashboard_notice',
				'type'      => 'hook',
				'hook_type' => 'success',
			];
		}
	}

	/**
	 * Action hooks.
	 * @return void
	 */
	private function init() {
		/**
		 * Core hooks
		 */
		add_action( 'admin_menu', [ $this, 'register_menu' ] );
		add_action( 'in_admin_header', [ $this, 'add_background_color' ] );

		/**
		 * Plugin hooks
		 */
		add_action( 'plausible_analytics_settings_api_connect_button', [ $this, 'connect_button' ] );
		add_action( 'plausible_analytics_settings_api_token_missing', [ $this, 'api_token_missing' ] );
		add_action( 'plausible_analytics_settings_option_disabled_by_self_hosted_domain', [ $this, 'option_disabled_by_self_hosted_domain' ] );
		add_action( 'plausible_analytics_settings_proxy_warning', [ $this, 'proxy_warning' ] );
		add_action( 'plausible_analytics_settings_enable_analytics_dashboard_notice', [ $this, 'enable_analytics_dashboard_notice' ] );
		add_action( 'plausible_analytics_settings_option_disabled_by_missing_api_token', [ $this, 'option_disabled_by_missing_api_token' ] );
		add_action( 'plausible_analytics_settings_option_disabled_by_proxy', [ $this, 'option_disabled_by_proxy' ] );
	}

	/**
	 * Load all available user roles as a list (sorted alphabetically) of checkboxes to be processed by the Settings
	 * API.
	 *
	 * @param string $slug
	 *
	 * @return array
	 */
	private function build_user_roles_array( $slug, $disable_elements = [] ) {
		$wp_roles = wp_roles()->roles ?? [];

		foreach ( $wp_roles as $id => $role ) {
			$roles_array[ $id ] = [
				'label' => $role[ 'name' ] ?? '',
				'slug'  => $slug,
				'type'  => 'checkbox',
				'value' => $id,
			];

			if ( in_array( $id, array_keys( $disable_elements ), true ) ) {
				$roles_array[ $id ][ 'disabled' ] = true;

				if ( ! empty( $disable_elements[ $id ] ) ) {
					$roles_array[ $id ][ 'checked' ] = $disable_elements[ $id ];
				}
			}
		}

		ksort( $roles_array, SORT_STRING );

		return $roles_array;
	}

	/**
	 * Register Menu.
	 * @since  1.0.0
	 * @access public
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
			if ( in_array( $user_role, Helpers::get_settings()[ 'expand_dashboard_access' ], true ) ) {
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
			[
				$this,
				'render_analytics_dashboard',
			]
		);

		// Setup `Plausible Analytics` page under Settings.
		add_options_page(
			esc_html__( 'Plausible Analytics', 'plausible-analytics' ),
			esc_html__( 'Plausible Analytics', 'plausible-analytics' ),
			'manage_options',
			'plausible_analytics',
			[
				$this,
				'settings_page',
			]
		);
	}

	/**
	 * A little hack to add some classes to the core #wpcontent div.
	 * @return void
	 */
	public function add_background_color() {
		if ( array_key_exists( 'page', $_GET ) && $_GET[ 'page' ] == 'plausible_analytics' ) {
			echo "<script>document.getElementById('wpcontent').classList += 'px-2.5 bg-gray-50 dark:bg-gray-85'; </script>";
		}
	}

	/**
	 * Statistics Page via Embed feature.
	 * @since  1.2.0
	 * @access public
	 * @return void
	 */
	public function render_analytics_dashboard() {
		global $current_user;

		$settings          = Helpers::get_settings();
		$analytics_enabled = $settings[ 'enable_analytics_dashboard' ];
		$shared_link       = $settings[ 'shared_link' ] ?: '';
		$self_hosted       = ! empty( $settings [ 'self_hosted_domain' ] );

		if ( $self_hosted ) {
			$shared_link = $settings[ 'self_hosted_shared_link' ];
		}

		$has_access             = false;
		$user_roles_have_access = ! empty( $settings[ 'expand_dashboard_access' ] ) ? array_merge(
			[ 'administrator' ],
			$settings[ 'expand_dashboard_access' ]
		) : [ 'administrator' ];

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
					esc_html__(
						'You don\'t have sufficient privileges to access the analytics dashboard. Please contact administrator of the website to grant you the access.',
						'plausible-analytics'
					)
				);

				return;
				?>
			</div>
		<?php
		endif;

		/**
		 * Prior to this version, the default value would contain an example "auth" key, i.e. XXXXXXXXX.
		 * When this option was saved to the database, underlying code would fail, throwing a CORS related error in browsers.
		 * Now, we explicitly check for the existence of this example "auth" key, and display a human readable error message to
		 * those who haven't properly set it up.
		 * @since v1.2.5
		 * For self-hosters the View Stats option doesn't need to be enabled, if a Shared Link is entered, we can assume they want to View Stats.
		 * For regular users, the shared link is provisioned by the API, so it shouldn't be empty.
		 * @since v2.0.3
		 */
		if ( ( ! $self_hosted && ! empty( $analytics_enabled ) && ! empty( $shared_link ) ) ||
			( $self_hosted && ! empty( $shared_link ) ) ||
			strpos( $shared_link, 'XXXXXX' ) !== false ) {
			$page_url = isset( $_GET[ 'page-url' ] ) ? esc_url( $_GET[ 'page-url' ] ) : '';

			// Append individual page URL if it exists.
			if ( $shared_link && $page_url ) {
				$shared_link .= "&page={$page_url}";
			}
			?>
			<div id="plausible-analytics-stats">
				<iframe plausible-embed=""
						src="<?php echo "{$shared_link}&embed=true&theme=light&background=transparent"; ?>"
						scrolling="no" loading="lazy" style="border: 0; width: 100%; height: 1750px; "></iframe>
				<script async src="https://plausible.io/js/embed.host.js"></script>
				<script>
					document.addEventListener('DOMContentLoaded', () => {
						let iframe = '';

						// Give iframe a chance to load.
						setTimeout(function () {
								iframe = document.getElementById('iFrameResizer0');

								/**
								 * Adblocker active.
								 */
								if (iframe === null) {
									let div = document.getElementById('plausible-analytics-stats');

									div.innerHTML = '<p style="color: red;"><strong><?php echo __(
										"Plausible Analytics\' statistics couldn\'t be loaded. Please disable your ad blocker.",
										'plausible-analytics'
									); ?></strong></p>';
								}
							},
							1500
						);

					});
				</script>
			</div>
			<?php
		} else {
			?>
			<div class="plausible-analytics-statistics-not-loaded">
				<p>
					<?php if ( $settings[ 'self_hosted_domain' ] ) : ?>
						<?php echo sprintf(
							__(
								'Please enter your <em>Shared Link</em> under <a href="%s">Self-Hosted Settings</a>.',
								'plausible-analytics'
							),
							admin_url( 'options-general.php?page=plausible_analytics&tab=self-hosted' )
						); ?>
					<?php else: ?>
						<?php echo sprintf(
							__( 'Please <a href="%s">click here</a> to enable <strong>View Stats in WordPress</strong>.', 'plausible-analytics' ),
							admin_url( 'options-general.php?page=plausible_analytics#is_shared_link' )
						);
						?>
					<?php endif; ?>
				</p>
			</div>
			<?php
		}
	}

	/**
	 * Display connect button.
	 * @return void
	 */
	public function connect_button() {
		$settings = Helpers::get_settings();

		if ( ! empty( $settings[ 'domain_name' ] ) && ! empty( $settings[ 'api_token' ] ) ): ?>

		<?php else: ?>
			<?php
			$url = sprintf( 'https://plausible.io/%s/settings/integrations?new_token=Wordpress', Helpers::get_domain() );
			?>
			<a href="<?php esc_attr_e( $url, 'plausible-analytics' ); ?>" target="_blank" class="plausible-analytics-btn">
				<?php esc_html_e( 'Connect to Plausible', 'plausible-analytics' ); ?>
			</a>
		<?php endif; ?>
		<?php
	}

	/**
	 * Renders the warning for the Enable Proxy option.
	 * @since 1.3.0
	 * @return void
	 */
	public function proxy_warning() {
		if ( ! empty( Helpers::get_settings()[ 'self_hosted_domain' ] ) ) {
			$this->option_disabled_by_self_hosted_domain();
		} else {
			echo sprintf(
				wp_kses(
					__(
						'After enabling this option, please check your Plausible dashboard to make sure stats are being recorded. Are stats not being recorded? Do <a href="%s" target="_blank">reach out to us</a>. We\'re here to help!',
						'plausible-analytics'
					),
					'post'
				),
				'https://plausible.io/contact'
			);
		}
	}

	/**
	 * Show notice when API token notice is disabled.
	 * @return void
	 */
	public function option_disabled_by_self_hosted_domain() {
		echo wp_kses(
			__(
				'This option is disabled, because the <strong>Domain Name</strong> setting is enabled under <em>Self-Hosted</em> settings.',
				'plausible-analytics'
			),
			'post'
		);
	}

	/**
	 * Renders the analytics dashboard link if the option is enabled.
	 * @since 2.0.0
	 * @return void
	 */
	public function enable_analytics_dashboard_notice() {
		if ( ! empty( Helpers::get_settings()[ 'enable_analytics_dashboard' ] ) ) {
			echo sprintf(
				wp_kses(
					__(
						'Your analytics dashboard is available <a href="%s">here</a>.',
						'plausible-analytics'
					),
					'post'
				),
				admin_url( 'index.php?page=plausible_analytics_statistics' )
			);
		}
	}

	/**
	 * Renders the Self-hosted warning if the Proxy is enabled.
	 * @since 1.3.3
	 * @return void
	 */
	public function option_disabled_by_proxy() {
		if ( Helpers::proxy_enabled() ) {
			echo wp_kses(
				__(
					'This option is disabled, because the <strong>Proxy</strong> setting is enabled under <em>Settings</em>.',
					'plausible-analytics'
				),
				'post'
			);
		}
	}

	/**
	 * @return void
	 */
	public function api_token_missing() {
		echo wp_kses(
			__( 'Please generate and insert the API token into the API token field above.', 'plausible-analytics' ),
			'post'
		);
	}

	/**
	 * @return void
	 */
	public function option_disabled_by_missing_api_token() {
		echo wp_kses(
			__( 'Please generate and insert the API token into the API token field above to enable this option.', 'plausible-analytics' ),
			'post'
		);
	}
}
