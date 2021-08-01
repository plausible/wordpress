<?php
/**
 * Plausible Analytics | Settings API.
 *
 * @since 1.3.0
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
	 * @return void|mixed
	 */
	public function render_page_header() {
		$screen = get_current_screen();

		// Bailout, if screen id doesn't match.
		if ( 'settings_page_plausible_analytics' !== $screen->id ) {
			return;
		}
		?>
		<div class="plausible-analytics-header">
			<div class="plausible-analytics-logo">
				<img src="<?php echo PLAUSIBLE_ANALYTICS_PLUGIN_URL . '/assets/dist/images/icon.png'; ?>" alt="<?php esc_html_e( 'Plausible Analytics', 'plausible-analytics' ); ?>" />
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
					'url'   => admin_url( 'options-general.php?page=plausible_analytics&tab=selfhosted' ),
					'class' => 'self-hosted' === $current_tab ? 'active' : '',
				],
				'advanced'    => [
					'name'  => esc_html__( 'Advanced', 'plausible-analytics' ),
					'url'   => admin_url( 'options-general.php?page=plausible_analytics&tab=advanced' ),
					'class' => 'advanced' === $current_tab ? 'active' : '',
				],
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
	 * Render Settings Page.
	 *
	 * @since  1.3.0
	 * @access public
	 *
	 * @return void
	 */
	public function settings_page() {

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
		// echo $this->get_header( esc_html__( 'Analytics', 'plausible-analytics' ) );

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
					esc_html( 'to generate your shared link from your Plausible Analytics dashboard. Make sure the link is not password protected.', 'plausible-analytics' ),
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
