<?php /** @noinspection HtmlUnknownTarget */

/**
 * Plausible Analytics | Settings API.
 * @since      1.3.0
 * @package    WordPress
 * @subpackage Plausible Analytics
 */

namespace Plausible\Analytics\WP\Admin\Settings;

use Exception;
use Plausible\Analytics\WP\Client;
use Plausible\Analytics\WP\ClientFactory;
use Plausible\Analytics\WP\Helpers;

class Page extends API {
	const OPTION_NOT_AVAILABLE_IN_CE_HOOK           = [
		[
			'label' => '',
			'slug'  => 'option_not_available_in_ce',
			'type'  => 'hook',
		],
	];

	const PROXY_WARNING_HOOK                        = [
		'label' => '',
		'slug'  => 'proxy_warning',
		'type'  => 'hook',
	];

	const OPTION_DISABLED_BY_PROXY_HOOK             = [
		'label' => '',
		'slug'  => 'option_disabled_by_proxy',
		'type'  => 'hook',
	];

	const API_TOKEN_MISSING_HOOK                    = [
		'label' => '',
		'slug'  => 'api_token_missing',
		'type'  => 'hook',
	];

	const OPTION_DISABLED_BY_MISSING_API_TOKEN_HOOK = [
		'label' => '',
		'slug'  => 'option_disabled_by_missing_api_token',
		'type'  => 'hook',
	];

	const ENABLE_ANALYTICS_DASH_NOTICE              = [
		'label'     => '',
		'slug'      => 'enable_analytics_dashboard_notice',
		'type'      => 'hook',
		'hook_type' => 'success',
	];

	const CAP_GOALS                                 = 'goals';

	const CAP_PROPS                                 = 'props';

	const CAP_FUNNELS                               = 'funnels';

	const CAP_REVENUE                               = 'revenue';

	/**
	 * @var array|array[] $fields
	 */
	public $fields = [];

	/**
	 * @var ClientFactory $client_factory
	 */
	private $client_factory;

	/**
	 * @var Client $client
	 */
	private $client;

	/**
	 * Constructor.
	 * @since  1.3.0
	 * @access public
	 * @return void
	 * @throws Exception
	 */
	public function __construct() {
		$this->init();

		$settings = Helpers::get_settings();

		$this->client_factory = new ClientFactory();
		$this->client         = $this->client_factory->build();
		$this->fields         = [
			'general'     => [
				[
					'label'  => esc_html__( 'Connect your website with Plausible Analytics', 'plausible-analytics' ),
					'slug'   => 'connect_to_plausible_analytics',
					'type'   => 'group',
					'desc'   => sprintf(
						wp_kses(
							__(
								'Ensure your domain name matches the one in <a href="%s" target="_blank">your Plausible account</a>, then <a class="hover:cursor-pointer underline plausible-create-api-token">create a Plugin Token</a> (link opens in a new window) and paste it into the \'Plugin Token\' field.',
								'plausible-analytics'
							),
							'post'
						),
						Helpers::get_hosted_domain_url() . '/sites'
					),
					'fields' => [
						[
							'label' => esc_html__( 'Domain name', 'plausible-analytics' ),
							'slug'  => 'domain_name',
							'type'  => 'text',
							'value' => Helpers::get_domain(),
						],
						[
							'label' => esc_html__( 'Plugin Token', 'plausible-analytics' ) .
								' - ' .
								'<a class="hover:cursor-pointer underline plausible-create-api-token">' .
								__( 'Create Token', 'plausible-analytics' ) .
								'</a>',
							'slug'  => 'api_token',
							'type'  => 'text',
							'value' => $settings[ 'api_token' ],
						],
						[
							'label'    => empty( $settings[ 'domain_name' ] ) || empty( $settings[ 'api_token' ] ) ? esc_html__( 'Connect', 'plausible-analytics' ) :
								esc_html__( 'Connected', 'plausible-analytics' ),
							'slug'     => 'connect_plausible_analytics',
							'type'     => 'button',
							'disabled' => empty( $settings[ 'domain_name' ] ) || empty( $settings[ 'api_token' ] ) || ! $this->client instanceof Client || $this->client->is_api_token_valid(),
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
						'404'                      => [
							'label' => esc_html__( '404 error pages', 'plausible-analytics' ),
							'docs'  => 'https://plausible.io/wordpress-analytics-plugin#how-to-track-404-error-pages',
							'slug'  => 'enhanced_measurements',
							'type'  => 'checkbox',
							'value' => '404',
							'caps'  => [ self::CAP_GOALS ],
						],
						'file-downloads'           => [
							'label' => esc_html__( 'File downloads', 'plausible-analytics' ),
							'docs'  => 'https://plausible.io/wordpress-analytics-plugin#how-to-track-file-downloads',
							'slug'  => 'enhanced_measurements',
							'type'  => 'checkbox',
							'value' => 'file-downloads',
							'caps'  => [ self::CAP_GOALS ],
						],
						'outbound-links'           => [
							'label' => esc_html__( 'Outbound links', 'plausible-analytics' ),
							'docs'  => 'https://plausible.io/wordpress-analytics-plugin#how-to-track-external-link-clicks',
							'slug'  => 'enhanced_measurements',
							'type'  => 'checkbox',
							'value' => 'outbound-links',
							'caps'  => [ self::CAP_GOALS ],
						],
						'pageview-props'           => [
							'label' => esc_html__( 'Authors and categories', 'plausible-analytics' ),
							'docs'  => 'https://plausible.io/wordpress-analytics-plugin#how-to-send-custom-properties',
							'slug'  => 'enhanced_measurements',
							'type'  => 'checkbox',
							'value' => 'pageview-props',
							'caps'  => [ self::CAP_PROPS ],
						],
						'affiliate-links'          => [
							'label'      => esc_html__( 'Cloaked affiliate links', 'plausible-analytics' ),
							'docs'       => 'https://plausible.io/wordpress-analytics-plugin#how-to-track-cloaked-affiliate-link-clicks',
							'slug'       => 'enhanced_measurements',
							'type'       => 'checkbox',
							'value'      => 'affiliate-links',
							'addtl_opts' => true,
							'caps'       => [ self::CAP_GOALS ],
						],
						'affiliate-links-patterns' => [
							'slug'        => 'affiliate_links',
							'description' => sprintf(
								__(
									'Enter the (partial) URLs you\'d like to track. E.g. enter <strong>/recommends/</strong> if you want to track <code>%s</code>.',
									'plausible-analytics'
								),
								get_home_url() . '/recommends/affiliate-product/'
							),
							'type'        => 'clonable_text',
							'value'       => Helpers::get_settings()[ 'affiliate_links' ] ?? [],
							'hidden'      => ! Helpers::is_enhanced_measurement_enabled( 'affiliate-links' ),
						],
						'revenue'                  => [
							'label' => esc_html__( 'Ecommerce revenue', 'plausible-analytics' ),
							'docs'  => 'https://plausible.io/wordpress-analytics-plugin#how-to-track-ecommerce-revenue',
							'slug'  => 'enhanced_measurements',
							'type'  => 'checkbox',
							'value' => 'revenue',
							'caps'  => [ self::CAP_GOALS, self::CAP_FUNNELS, self::CAP_PROPS, self::CAP_REVENUE ],
						],
						'form-completions'         => [
							'label' => esc_html__( 'Form completions', 'plausible-analytics' ),
							'docs'  => 'https://plausible.io/wordpress-analytics-plugin#how-to-track-form-completions',
							'slug'  => 'enhanced_measurements',
							'type'  => 'checkbox',
							'value' => 'form-completions',
							'caps'  => [ self::CAP_GOALS ],
						],
						'user-logged-in'           => [
							'label' => esc_html__( 'Logged-in user status', 'plausible-analytics' ),
							'docs'  => 'https://plausible.io/wordpress-analytics-plugin#how-to-track-logged-in-user-status',
							'slug'  => 'enhanced_measurements',
							'type'  => 'checkbox',
							'value' => 'user-logged-in',
							'caps'  => [ self::CAP_PROPS ],
						],
						'search'                   => [
							'label' => esc_html__( 'Search queries', 'plausible-analytics' ),
							'docs'  => 'https://plausible.io/wordpress-analytics-plugin#how-to-enable-site-search-tracking',
							'slug'  => 'enhanced_measurements',
							'type'  => 'checkbox',
							'value' => 'search',
							'caps'  => [ self::CAP_GOALS ],
						],
						'advanced-options'         => [
							'label'  => esc_html__( 'Advanced options', 'plausible-analytics' ),
							'slug'   => 'advanced_options',
							'type'   => 'toggle_group',
							'fields' => [
								'tagged-events' => [
									'label' => esc_html__( 'Custom events', 'plausible-analytics' ),
									'docs'  => 'https://plausible.io/wordpress-analytics-plugin#how-to-setup-custom-events-to-track-goal-conversions',
									'slug'  => 'enhanced_measurements',
									'type'  => 'checkbox',
									'value' => 'tagged-events',
									'caps'  => [ self::CAP_GOALS ],
								],
								'hash'          => [
									'label' => esc_html__( 'Hash-based routing', 'plausible-analytics' ),
									'docs'  => 'https://plausible.io/wordpress-analytics-plugin#how-to-enable-hash-based-url-tracking',
									'slug'  => 'enhanced_measurements',
									'type'  => 'checkbox',
									'value' => 'hash',
									'caps'  => [],
								],
								'compat'        => [
									'label' => esc_html__( 'IE compatibility', 'plausible-analytics' ),
									'docs'  => 'https://plausible.io/wordpress-analytics-plugin#how-to-track-visitors-who-use-internet-explorer',
									'slug'  => 'enhanced_measurements',
									'type'  => 'checkbox',
									'value' => 'compat',
									'caps'  => [],
								],
							],
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
					'label'  => esc_html__( 'Plausible Community Edition', 'plausible-analytics' ),
					'slug'   => 'is_self_hosted',
					'type'   => 'group',
					'desc'   => sprintf(
						'%1$s <a href="%2$s" target="_blank">%3$s</a>',
						wp_kses(
							__(
								'If you\'re using Plausible Community Edition on your own infrastructure, enter the domain name where you installed it to enable the integration with your self-hosted instance. Multisites can use the <code>PLAUSIBLE_SELF_HOSTED_DOMAIN</code> constant to define the URL for all subsites at once.',
								'plausible-analytics'
							),
							'post'
						),
						esc_url( 'https://plausible.io/self-hosted-web-analytics/' ),
						esc_html__( 'Learn more about Plausible Community Edition.', 'plausible-analytics' )
					),
					'fields' => [
						[
							'label'       => esc_html__( 'Domain name', 'plausible-analytics' ),
							'slug'        => 'self_hosted_domain',
							'type'        => 'text',
							'value'       => defined( 'PLAUSIBLE_SELF_HOSTED_DOMAIN' ) ? PLAUSIBLE_SELF_HOSTED_DOMAIN : $settings[ 'self_hosted_domain' ],
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
						'<ol><li>' . __(
							'<a href="%s" target="_blank">Create a secure and private shared link</a> in your Plausible account.',
							'plausible-analytics'
						) . '<li>' . __(
							'Paste the shared link in the text box to view your stats in your WordPress dashboard.',
							'plausible-analytics'
						) . '</li>' . '</li></ol>',
						esc_url( 'https://plausible.io/docs/embed-dashboard' )
					),
					'fields' => [
						[
							'label'       => esc_html__( 'Shared link', 'plausible-analytics' ),
							'slug'        => 'self_hosted_shared_link',
							'type'        => 'text',
							'value'       => $settings[ 'self_hosted_shared_link' ],
							'placeholder' => sprintf(
								wp_kses( __( 'E.g. %s/share/%s?auth=XXXXXXXXXXXX', 'plausible-analytics' ), 'post' ),
								Helpers::get_hosted_domain_url(),
								Helpers::get_domain()
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

		/**
		 * If self-hosted domain setting has a value, add option disabled notice to Ecommerce revenue toggle.
		 */
		if ( ! empty( $settings[ 'self_hosted_domain' ] ) ) {
			$fields = $this->fields[ 'general' ][ 1 ][ 'fields' ];

			array_splice( $fields, 7, 0, self::OPTION_NOT_AVAILABLE_IN_CE_HOOK );

			$this->fields[ 'general' ][ 1 ][ 'fields' ] = $fields;
		}

		/**
		 * If proxy is enabled, or self-hosted domain has a value, display warning box.
		 * @see self::proxy_warning()
		 */
		if ( Helpers::proxy_enabled() || ! empty( $settings[ 'self_hosted_domain' ] ) ) {
			$this->fields[ 'general' ][ 2 ][ 'fields' ][] = self::PROXY_WARNING_HOOK;
		}

		/**
		 * If proxy is enabled, disable Self-hosted fields and display a warning.
		 */
		if ( Helpers::proxy_enabled() ) {
			$this->fields[ 'self-hosted' ][ 0 ][ 'fields' ][] = self::OPTION_DISABLED_BY_PROXY_HOOK;
			$this->fields[ 'self-hosted' ][ 1 ][ 'fields' ][] = self::OPTION_DISABLED_BY_PROXY_HOOK;
		}

		/**
		 * No Plugin Token is entered.
		 */
		if ( empty( $settings[ 'api_token' ] ) ) {
			$this->fields[ 'general' ][ 0 ][ 'fields' ][] = self::API_TOKEN_MISSING_HOOK;
			$this->fields[ 'general' ][ 3 ][ 'fields' ][] = self::OPTION_DISABLED_BY_MISSING_API_TOKEN_HOOK;
		}

		/**
		 * If View Stats is enabled, display notice.
		 */
		if ( ! empty( $settings[ 'api_token' ] ) && ! empty( $settings[ 'enable_analytics_dashboard' ] ) ) {
			$this->fields[ 'general' ][ 3 ][ 'fields' ][] = self::ENABLE_ANALYTICS_DASH_NOTICE;
		}
	}

	/**
	 * Init action hooks.
	 * @return void
	 */
	private function init() {
		/**
		 * WP Core hooks
		 */
		add_action( 'admin_menu', [ $this, 'register_menu' ] );
		add_action( 'in_admin_header', [ $this, 'add_background_color' ] );

		/**
		 * Hooks that run on settings page.
		 */
		new Hooks();
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
		$user_roles = $user->roles ?? [];
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

		$settings = Helpers::get_settings();

		/**
		 * Don't show the Analytics dashboard, if View Stats is disabled.
		 */
		if ( ! empty( $settings[ 'enable_analytics_dashboard' ] ) || ( ! empty( $settings[ 'self_hosted_domain' ] ) && ! empty( $settings[ 'self_hosted_shared_link' ] ) ) ) {
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
		}

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
		 * Now, we explicitly check for the existence of this example "auth" key, and display a human-readable error message to
		 * those who haven't properly set it up.
		 * @since v1.2.5
		 * For self-hosters the View Stats option doesn't need to be enabled, if a Shared Link is entered, we can assume they want to View Stats.
		 * For regular users, the shared link is provisioned by the API, so it shouldn't be empty.
		 * @since v2.0.3
		 */
		if ( ( ! $self_hosted && ! empty( $analytics_enabled ) && ! empty( $shared_link ) ) || ( $self_hosted && ! empty( $shared_link ) ) || strpos( $shared_link, 'XXXXXX' ) !== false ) {
			$page_url = isset( $_GET[ 'page-url' ] ) ? esc_url( $_GET[ 'page-url' ] ) : '';

			// Append individual page URL if it exists.
			if ( $shared_link && $page_url ) {
				$shared_link .= "&page={$page_url}";
			}

			$hosted_domain = Helpers::get_hosted_domain_url();
			?>
			<div id="plausible-analytics-stats">
				<iframe plausible-embed=""
						src="<?php echo "{$shared_link}&embed=true&theme=light&background=transparent"; ?>"
						loading="lazy" style="border: 0; width: 100%; height: 1750px; "></iframe>
				<script async src="<?php echo $hosted_domain; ?>/js/embed.host.js"></script>
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
							__(
								'Please <a href="%s">click here</a> to enable <strong>View Stats in WordPress</strong>.',
								'plausible-analytics'
							),
							admin_url( 'options-general.php?page=plausible_analytics#is_shared_link' )
						);
						?>
					<?php endif; ?>
				</p>
			</div>
			<?php
		}
	}

	private function token_has_cap( $caps ) {
		static $stored_caps = [];

		if ( empty( $stored_caps ) ) {
			$stored_caps = get_option( 'plausible_analytics_api_token_caps', [] );
		}

		foreach ( $caps as $cap ) {
			if ( empty( $stored_caps[ $cap ] ) ) {
				return false;
			}
		}

		return true;
	}
}
