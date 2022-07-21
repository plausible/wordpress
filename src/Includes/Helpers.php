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
	 * Get Plain Domain.
	 *
	 * @since  1.0.0
	 * @access public
	 *
	 * @return string
	 */
	public static function get_domain() {
		$site_url = site_url();

		return preg_replace( '/^http(s?)\:\/\/(www\.)?/i', '', $site_url );
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
		$settings  = self::get_settings();
		$file_name = 'script';

		foreach ( [ 'outbound-links', 'file-downloads', 'compat', 'hash' ] as $extension ) {
			if ( ! empty( $settings[ $extension ] ) && $settings[ $extension ][0] === '1' ) {
				$file_name .= '.' . $extension;
			}
		}

		return self::get_script_url_path() . $file_name . '.js';
	}

	/**
	 * Determine the script's path.
	 *
	 * @return string
	 */
	private static function get_script_url_path() {
		$settings = self::get_settings();

		// Early return when there's a script path.
		if ( ! empty( $settings['script_path'] ) && is_string( $settings['script_path'] ) ) {
			return $settings['script_path'];
		}

		$domain         = $settings['domain_name'];
		$default_domain = 'plausible.io';

		// Triggered when self-hosted analytics is enabled.
		if (
			! empty( $settings['is_self_hosted_analytics'] ) &&
			'true' === $settings['is_self_hosted_analytics']
		) {
			$default_domain = $settings['self_hosted_domain'];
		}

		$path = "https://{$default_domain}/js/";

		// Triggered when custom domain is enabled.
		if (
			! empty( $settings['custom_domain'] ) &&
			'true' === $settings['custom_domain']
		) {
			$custom_domain_prefix = $settings['custom_domain_prefix'];
			$path                 = "https://{$custom_domain_prefix}.{$domain}/js/";
		}

		return $path;
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

		return "https://plausible.io/{$domain}";
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
		$individual_settings = ! empty( $settings[ $name ] ) ? $settings[ $name ] : '';
		?>
		<label class="plausible-analytics-switch">
			<input <?php checked( $individual_settings, 'true' ); ?> class="plausible-analytics-switch-checkbox" name="plausible_analytics_settings[<?php echo $name; ?>]" value="1" type="checkbox"/>
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
		$settings = get_option( 'plausible_analytics_settings', [] );

		// Keep around for backwards compatibility reasons.
		$track_outbound_links = apply_filters( 'plausible_analytics_enable_outbound_links', isset( $settings['outbound-links'][0] ) ? $settings['outbound-links'][0] : true );
		if ( $track_outbound_links ) {
			$settings['outbound-links'][0] = 1;
		}

		return $settings;
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
		// Early return when there's an event API path set.
		if ( ! empty( $settings['event_path'] ) && is_string( $settings['event_path'] ) ) {
			return trailingslashit( $settings['event_path'] ) . 'event';
		}

		$url = 'https://plausible.io/api/event';
		// Triggered when self hosted analytics is enabled.
		if (
			! empty( $settings['is_self_hosted_analytics'] ) &&
			'true' === $settings['is_self_hosted_analytics']
		) {
			$default_domain = $settings['self_hosted_domain'];
			$url            = "https://{$default_domain}/api/event";
		}

		// Triggered when custom domain is enabled.
		if (
			! empty( $settings['custom_domain'] ) &&
			'true' === $settings['custom_domain']
		) {
			$domain               = $settings['domain_name'];
			$custom_domain_prefix = $settings['custom_domain_prefix'];
			$url                  = "https://{$custom_domain_prefix}.{$domain}/api/event";
		}

		return $url;
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
