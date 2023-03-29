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
		$self_hosted_domain = ! empty( $settings['self_hosted_domain'] ) ? $settings['self_hosted_domain'] : 'example.com';
		$shared_link        = ! empty( $settings['shared_link'] ) ? $settings['shared_link'] : '';
		$excluded_pages     = ! empty( $settings['excluded_pages'] ) ? $settings['excluded_pages'] : '/imprint, /privacy-policy';
		$is_shared_link     = ! empty( $settings['is_shared_link'] ) ? (bool) $settings['is_shared_link'] : false;
		$is_exclude_pages   = ! empty( $settings['is_exclude_pages'] ) ? (bool) $settings['is_exclude_pages'] : false;
		$is_selfhosted      = ! empty( $settings['is_self_hosted_plausible_analytics'] ) ? (bool) $settings['is_self_hosted_plausible_analytics'] : false;

		$this->fields = [
			'general'     => [
				[
					'label'  => esc_html__( 'Connect your website with Plausible Analytics', 'plausible-analytics' ),
					'slug'   => 'connect_to_plausible_analytics',
					'type'   => 'group',
					'desc'   => sprintf(
						'%1$s <a href="%2$s" target="_blank">%3$s</a> %4$s',
						esc_html__( 'We have fetched the domain name for which Plausible Analytics will be used. We assume that you have already setup the domain on our website.', 'plausible-analytics' ),
						esc_url( 'https://docs.plausible.io/register-account' ),
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
					'desc'   => '<strong>' . esc_html__( 'Note:', 'plausible-analytics' ) . '</strong> ' . esc_html__( 'for each of these extensions, you have to set the goals manually!', 'plausible-analytics' ) . '<br><br>'
								. sprintf( esc_html__( 'By default, we load just the %1$s extension, you can enable other extensions here.', 'plausible-analytics' ), '<a href="https://plausible.io/docs/outbound-link-click-tracking"></a><code>outbound-links</code></a>' ),
					'toggle' => [
						'anchor' => 'https://plausible.io/docs/script-extensions',
						'label'  => __( 'Documentation', 'plausible-analytics' ),
					],
					'fields' => [
						'outbound-links' => [
							'label'      => esc_html__( 'Outbound links', 'plausible-analytics' ),
							'docs'       => 'https://plausible.io/docs/outbound-link-click-tracking#step-2-create-a-custom-event-goal-in-your-plausible-analytics-account',
							'docs_label' => esc_html__( 'Goal setup', 'plausible-analytics' ),
							'slug'       => 'outbound-links',
							'type'       => 'checkbox',
							'value'      => '1',
						],
						'file-downloads' => [
							'label'      => esc_html__( 'File downloads', 'plausible-analytics' ),
							'docs'       => 'https://plausible.io/docs/file-downloads-tracking#step-2-create-a-custom-event-goal-in-your-plausible-analytics-account',
							'docs_label' => esc_html__( 'Goal setup', 'plausible-analytics' ),
							'slug'       => 'file-downloads',
							'type'       => 'checkbox',
							'value'      => '1',
						],
						'hash'           => [
							'label'      => esc_html__( 'Hash-based routing', 'plausible-analytics' ),
							'docs'       => 'https://plausible.io/docs/hash-based-routing',
							'docs_label' => esc_html__( 'Documentation', 'plausible-analytics' ),
							'slug'       => 'hash',
							'type'       => 'checkbox',
							'value'      => '1',
						],
						'compat'         => [
							'label'      => esc_html__( 'IE compatibility', 'plausible-analytics' ),
							'docs'       => 'https://plausible.io/docs/script-extensions#scriptcompatjs',
							'docs_label' => esc_html__( 'Documentation', 'plausible-analytics' ),
							'slug'       => 'compat',
							'type'       => 'checkbox',
							'value'      => '1',
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
						esc_url( 'https://plausible.io/docs/shared-links' ),
						esc_html__( 'See how &raquo;', 'plausible-analytics' ),
						esc_html__( 'Enable this setting and paste your shared link to view your stats in your WordPress dashboard.', 'plausible-analytics' ),
						esc_html__( 'View your site statistics within your WordPress Dashboard.', 'plausible-analytics' ),
						admin_url( 'index.php?page=plausible-analytics-statistics' ),
						esc_html__( 'View Statistics &raquo;', 'plausible-analytics' )
					),
					'toggle' => $is_shared_link,
					'fields' => [
						[
							'label'       => esc_html__( 'Shared Link', 'plausible-analytics' ),
							'slug'        => 'shared_link',
							'type'        => 'text',
							'value'       => $shared_link,
							'placeholder' => "https://plausible.io/share/{$domain}?auth=XXXXXXXXXXXX"
						],
					],
				],
				[
					'label'  => esc_html__( 'Exclude specific pages from being tracked', 'plausible-analytics' ),
					'slug'   => 'is_exclude_pages',
					'type'   => 'group',
					'desc'   => sprintf(
						'%1$s <a href="%2$s" target="_blank">%3$s</a>',
						esc_html__( 'Exclude certain pages from being tracked', 'plausible-analytics' ),
						esc_url( 'https://plausible.io/docs/excluding-pages#2-add-the-pages-youd-like-to-exclude-from-being-tracked' ),
						esc_html__( 'See syntax &raquo;', 'plausible-analytics' )
					),
					'toggle' => $is_exclude_pages,
					'fields' => [
						[
							'label' => esc_html__( 'Excluded pages', 'plausible-analytics' ),
							'slug'  => 'excluded_pages',
							'type'  => 'text',
							'value' => $excluded_pages,
						],
					],
				],
				[
					'label'  => esc_html__( 'Track analytics for user roles', 'plausible-analytics' ),
					'slug'   => 'can_role_track_analytics',
					'type'   => 'group',
					'desc'   => esc_html__( 'By default, we won\'t be tracking visits of any of the user roles listed above. If you want to track analytics for specific user roles then please check the specific user role setting.', 'plausible-analytics' ),
					'toggle' => false,
					'fields' => [
						'administrator' => [
							'label' => esc_html__( 'Administrator', 'plausible-analytics' ),
							'slug'  => 'track_analytics',
							'type'  => 'checkbox',
							'value' => 'administrator',
						],
						'editor'        => [
							'label' => esc_html__( 'Editor', 'plausible-analytics' ),
							'slug'  => 'track_analytics',
							'type'  => 'checkbox',
							'value' => 'editor',
						],
						'author'        => [
							'label' => esc_html__( 'Author', 'plausible-analytics' ),
							'slug'  => 'track_analytics',
							'type'  => 'checkbox',
							'value' => 'author',
						],
						'contributor'   => [
							'label' => esc_html__( 'Contributor', 'plausible-analytics' ),
							'slug'  => 'track_analytics',
							'type'  => 'checkbox',
							'value' => 'contributor',
						],
					],
				],
				[
					'label'  => esc_html__( 'Show the stats dashboard to specific user roles', 'plausible-analytics' ),
					'slug'   => 'can_access_analytics_page',
					'type'   => 'group',
					'desc'   => esc_html__( 'By default, we are only showing the stats dashboard to admin users. If you want to allow the dashboard to be displayed for specific user roles, then please check them above.', 'plausible-analytics' ),
					'toggle' => false,
					'fields' => [
						'editor'      => [
							'label' => esc_html__( 'Editor', 'plausible-analytics' ),
							'slug'  => 'access_to_user_roles',
							'type'  => 'checkbox',
							'value' => 'editor',
						],
						'author'      => [
							'label' => esc_html__( 'Author', 'plausible-analytics' ),
							'slug'  => 'access_to_user_roles',
							'type'  => 'checkbox',
							'value' => 'author',
						],
						'contributor' => [
							'label' => esc_html__( 'Contributor', 'plausible-analytics' ),
							'slug'  => 'access_to_user_roles',
							'type'  => 'checkbox',
							'value' => 'contributor',
						],
					],
				],
			],
			'self-hosted' => [
				[
					'label'  => esc_html__( 'Self-hosted Plausible Analytics?', 'plausible-analytics' ),
					'slug'   => 'is_self_hosted_plausible_analytics',
					'type'   => 'group',
					'desc'   => sprintf(
						'%1$s <a href="%2$s" target="_blank">%3$s</a>',
						esc_html__( 'If you\'re self-hosting Plausible on your own infrastructure, enter the domain name where you installed it to enable the integration with your self-hosted instance. Learn more', 'plausible-analytics' ),
						esc_url( 'https://plausible.io/self-hosted-web-analytics/' ),
						esc_html__( 'about Plausible Self-Hosted.', 'plausible-analytics' )
					),
					'toggle' => $is_selfhosted,
					'fields' => [
						[
							'label' => esc_html__( 'Domain Name', 'plausible-analytics' ),
							'slug'  => 'self_hosted_domain',
							'type'  => 'text',
							'value' => $self_hosted_domain,
						],
					],
				],
			],
		];

		add_action( 'admin_menu', [ $this, 'register_menu' ] );
		add_action( 'in_admin_header', [ $this, 'render_page_header' ] );
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
		// Setup `Analytics` page under Dashboard.
		add_dashboard_page(
			esc_html__( 'Analytics', 'plausible-analytics' ),
			esc_html__( 'Analytics', 'plausible-analytics' ),
			'manage_options',
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
					'name'  => esc_html__( 'Self Hosted', 'plausible-analytics' ),
					'url'   => admin_url( 'options-general.php?page=plausible_analytics&tab=self-hosted' ),
					'class' => 'self-hosted' === $current_tab ? 'active' : '',
				],
				// 'advanced'    => [
				// 	'name'  => esc_html__( 'Advanced', 'plausible-analytics' ),
				// 	'url'   => admin_url( 'options-general.php?page=plausible_analytics&tab=advanced' ),
				// 	'class' => 'advanced' === $current_tab ? 'active' : '',
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

		$settings                  = Helpers::get_settings();
		$domain                    = Helpers::get_domain();
		$is_shared_link            = ! empty( $settings['is_shared_link'] ) ? (bool) $settings['is_shared_link'] : 'false';
		$shared_link               = ! empty( $settings['shared_link'] ) ?
			$settings['shared_link'] :
			'';
		$can_access_analytics_page = ! empty( $settings['can_access_analytics_page'] ) ?
			$settings['can_access_analytics_page'] :
			false;

		if ( $can_access_analytics_page ) {
			$has_access             = false;
			$user_roles_have_access = ! empty( $settings['access_to_user_roles'] ) ? $settings['access_to_user_roles'] : [ 'administrator' ];

			foreach ( $current_user->roles as $role ) {
				if ( in_array( $role, $user_roles_have_access, true ) ) {
					$has_access = true;
				}
			}

			// Show error, if not having access.
			if ( ! $has_access ) {
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
			}
		}

		/**
		 * Prior to this version, the default value would contain an example "auth" key, i.e. XXXXXXXXX.
		 *
		 * When this option was saved to the database, underlying code would fail, throwing a CORS related error in browsers.
		 *
		 * Now, we explicitly check for the existence of this example "auth" key, and display a human readable error message to
		 * those we haven't properly set it up.
		 *
		 * @since v1.2.5
		 */
		if ( $is_shared_link && ( ! empty( $shared_link ) || strpos( $shared_link, 'XXXXXX' ) !== false ) ) {
			// Append individual page URL if it exists.
			if ( $shared_link && isset( $_GET['page-url'] ) ) {
				$shared_link .= "&page={$_GET[ 'page-url' ]}";
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
}
