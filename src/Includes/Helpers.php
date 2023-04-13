<?php
/**
 * Plausible Analytics | Helpers
 *
 * @since      1.0.0
 *
 * @package    WordPress
 * @subpackage Plausible Analytics
 */

namespace Plausible\Analytics\WP\Includes;

// Bailout, if accessed directly.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class Helpers {

	/**
	 * Get Plain Domain (without protocol or www. subdomain)
	 *
	 * @since  1.0.0
	 * @access public
	 *
	 * @return string
	 */
	public static function get_domain() {
		$url = home_url();

		return preg_replace( '/^http(s?)\:\/\/(www\.)?/i', '', $url );
	}

	/**
	 * Get Analytics URL.
	 *
	 * @since  1.0.0
	 * @access public
	 *
	 * @return string
	 */
	public static function get_analytics_url() {
		$settings       = self::get_settings();
		$default_domain = 'plausible.io';
		$file_name      = 'plausible';

		foreach ( [ 'outbound-links', 'file-downloads', 'tagged-events', 'compat', 'hash' ] as $extension ) {
			if ( in_array( $extension, $settings['enhanced_measurements'], true ) ) {
				$file_name .= '.' . $extension;
			}
		}

		// Load exclusions.js if any excluded pages are set.
		if ( ! empty( $settings['excluded_pages'] ) ) {
			$file_name .= '.' . 'exclusions';
		}

		// Triggered when self-hosted analytics is enabled.
		if (
			! empty( $settings['self_hosted_domain'] )
		) {
			$default_domain = $settings['self_hosted_domain'];
		}

		$url = "https://{$default_domain}/js/{$file_name}.js";

		return esc_url( $url );
	}

	/**
	 * Get Dashboard URL.
	 *
	 * @since  1.0.0
	 * @access public
	 *
	 * @return string
	 */
	public static function get_analytics_dashboard_url() {
		$settings = self::get_settings();
		$domain   = $settings['domain_name'];

		return esc_url( "https://plausible.io/{$domain}" );
	}

	/**
	 * Toggle Switch HTML Markup.
	 *
	 * @param string $name Name of the toggle switch.
	 *
	 * @since  1.0.0
	 * @access public
	 *
	 * @return void
	 */
	public static function display_toggle_switch( $name ) {
		$settings            = Helpers::get_settings();
		$individual_settings = ! empty( $settings[ $name ] ) ? esc_html( $settings[ $name ] ) : '';
		?>
		<label class="plausible-analytics-switch">
			<input <?php checked( $individual_settings, 'true' ); ?> class="plausible-analytics-switch-checkbox" name="plausible_analytics_settings[<?php echo esc_attr( $name ); ?>]" value="1" type="checkbox"/>
			<span class="plausible-analytics-switch-slider"></span>
		</label>
		<?php
	}

	/**
	 * Get Settings.
	 *
	 * @since  1.0.0
	 * @access public
	 *
	 * @return array
	 */
	public static function get_settings() {
		$defaults = [
			'domain_name'             => '',
			'enhanced_measurements'   => [],
			'shared_link'             => '',
			'excluded_pages'          => '',
			'tracked_user_roles'      => [],
			'expand_dashboard_access' => [],
			'self_hosted_domain'      => '',
		];

		$settings = get_option( 'plausible_analytics_settings', [] );

		return wp_parse_args( $settings, $defaults );
	}

	/**
	 * Get Data API URL.
	 *
	 * @since  1.2.2
	 * @access public
	 *
	 * @return string
	 */
	public static function get_data_api_url() {
		$settings = self::get_settings();
		$url      = 'https://plausible.io/api/event';

		// Triggered when self hosted analytics is enabled.
		if (
			! empty( $settings['self_hosted_domain'] )
		) {
			$default_domain = $settings['self_hosted_domain'];
			$url            = "https://{$default_domain}/api/event";
		}

		return esc_url( $url );
	}

	/**
	 * Get Quick Actions.
	 *
	 * @since  1.3.0
	 * @access public
	 *
	 * @return array
	 */
	public static function get_quick_actions() {
		return [
			'view-docs'        => [
				'label' => esc_html__( 'Documentation', 'plausible-analytics' ),
				'url'   => esc_url( 'https://docs.plausible.io/' ),
			],
			'report-issue'     => [
				'label' => esc_html__( 'Report an issue', 'plausible-analytics' ),
				'url'   => esc_url( 'https://github.com/plausible/wordpress/issues/new' ),
			],
			'translate-plugin' => [
				'label' => esc_html__( 'Translate Plugin', 'plausible-analytics' ),
				'url'   => esc_url( 'https://translate.wordpress.org/projects/wp-plugins/plausible-analytics/' ),
			],
		];
	}

	/**
	 * Render Quick Actions
	 *
	 * @since  1.3.0
	 * @access public
	 *
	 * @return string
	 */
	public static function render_quick_actions() {
		ob_start();
		$quick_actions = self::get_quick_actions();
		?>
		<div class="plausible-analytics-quick-actions">
		<?php
		if ( ! empty( $quick_actions ) && count( $quick_actions ) > 0 ) {
			?>
			<div class="plausible-analytics-quick-actions-title">
				<?php esc_html_e( 'Quick Links', 'plausible-analytics' ); ?>
			</div>
			<ul>
			<?php
			foreach ( $quick_actions as $quick_action ) {
				?>
				<li>
					<a target="_blank" href="<?php echo $quick_action['url']; ?>" title="<?php echo $quick_action['label']; ?>">
						<?php echo $quick_action['label']; ?>
					</a>
				</li>
				<?php
			}
			?>
			</ul>
			<?php
		}
		?>
		</div>
		<?php
		return ob_get_clean();
	}

	/**
	 * Clean variables using `sanitize_text_field`.
	 * Arrays are cleaned recursively. Non-scalar values are ignored.
	 *
	 * @param string|array $var Sanitize the variable.
	 *
	 * @since  1.3.0
	 * @access public
	 *
	 * @return string|array
	 */
	public static function clean( $var ) {
		if ( is_array( $var ) ) {
			return array_map( [ __CLASS__, __METHOD__ ], $var );
		}

		return is_scalar( $var ) ? sanitize_text_field( wp_unslash( $var ) ) : $var;
	}

	/**
	 * Get user role for the logged-in user.
	 *
	 * @since  1.3.0
	 * @access public
	 *
	 * @return string
	 */
	public static function get_user_role() {
		global $current_user;

		$user_roles = $current_user->roles;

		return array_shift( $user_roles );
	}
}
