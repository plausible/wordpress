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

use Exception;
use WpOrg\Requests\Exception\InvalidArgument;

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
	public static function get_js_url( $local = false ) {
		$settings       = self::get_settings();
		$file_name      = self::get_filename( $local );
		$default_domain = 'plausible.io';

		/**
		 * If Avoid ad blockers is enabled, return URL to local file.
		 */
		if ( $local && ! empty( $settings['avoid_ad_blockers'][0] ) ) {
			return esc_url(
				self::get_proxy_resource( 'cache_url' ) . $file_name . '.js'
			);
		}

		/**
		 * Set $defailt_domain to self_hosted_domain if it exists.
		 */
		if (
			! empty( $settings['self_hosted_domain'] )
		) {
			$default_domain = $settings['self_hosted_domain'];
		}

		$url = "https://{$default_domain}/js/{$file_name}.js";

		return esc_url( $url );
	}

	/**
	 * A convenient way to retrieve the absolute path to the local JS file.
	 *
	 * @return string
	 * @throws Exception
	 */
	public static function get_js_path() {
		return self::get_proxy_resource( 'cache_dir' ) . self::get_filename( true ) . '.js';
	}

	/**
	 * Get filename (without file extension)
	 *
	 * @since 1.3.0
	 *
	 * @return string
	 */
	public static function get_filename( $local = false ) {
		$settings  = self::get_settings();
		$file_name = 'plausible';

		if ( $local && ! empty( $settings['avoid_ad_blockers'][0] ) ) {
			return self::get_proxy_resource( 'file_alias' );
		}

		foreach ( [ 'outbound-links', 'file-downloads', 'tagged-events', 'compat', 'hash' ] as $extension ) {
			if ( in_array( $extension, $settings['enhanced_measurements'], true ) ) {
				$file_name .= '.' . $extension;
			}
		}

		// Load exclusions.js if any excluded pages are set.
		if ( ! empty( $settings['excluded_pages'] ) ) {
			$file_name .= '.' . 'exclusions';
		}

		return $file_name;
	}

	/**
	 * Downloads the plausible.js file to this server.
	 *
	 * @since 1.3.0
	 *
	 * @param string $remote_file Full URL to file to download.
	 * @param string $local_file  Absolutate path to where to store the $remote_file.
	 *
	 * @return bool True when successfull. False if it fails.
	 *
	 * @throws InvalidArgument
	 * @throws Exception
	 */
	public static function download_file( $remote_file, $local_file ) {
		$file_contents = wp_remote_get( $remote_file );

		if ( is_wp_error( $file_contents ) ) {
			// TODO: add error handling?
			return false;
		}

		/**
		 * Some servers don't do a full overwrite if file already exists, so we delete it first.
		 */
		if ( file_exists( $local_file ) ) {
			unlink( $local_file );
		}

		$write = file_put_contents( $local_file, wp_remote_retrieve_body( $file_contents ) );

		return $write > 0;
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
			'proxy'                   => '',
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
	 * Get (and generate/store if non-existent) all necessary proxy resources.
	 *
	 * @param mixed $resource_name
	 * @return mixed
	 * @throws Exception
	 */
	public static function get_proxy_resource( $resource_name ) {
		static $resources;

		if ( $resources === null ) {
			$resources = get_option( 'plausible_analytics_proxy_resources', [] );
		}

		if ( empty( $resources ) ) {
			$cache_dir = bin2hex( random_bytes( 5 ) );

			$resources = [
				'namespace'  => bin2hex( random_bytes( 3 ) ),
				'base'       => bin2hex( random_bytes( 2 ) ),
				'endpoint'   => bin2hex( random_bytes( 4 ) ),
				'cache_dir'  => trailingslashit( wp_upload_dir()['basedir'] ) . trailingslashit( $cache_dir ),
				'cache_url'  => trailingslashit( wp_upload_dir()['baseurl'] ) . trailingslashit( $cache_dir ),
				'file_alias' => bin2hex( random_bytes( 4 ) ),
			];

			update_option( 'plausible_analytics_proxy_resources', $resources );
		}

		/**
		 * Create the cache directory if it doesn't exist.
		 */
		if ( $resource_name === 'cache_dir' && ! is_dir( $resources[ $resource_name ] ) ) {
			wp_mkdir_p( $resources[ $resource_name ] );
		}

		return isset( $resources[ $resource_name ] ) ? $resources[ $resource_name ] : '';
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

		if ( ! empty( $settings['avoid_ad_blockers'][0] ) ) {
			$namespace = self::get_proxy_resource( 'namespace' );
			$base      = self::get_proxy_resource( 'base' );
			$endpoint  = self::get_proxy_resource( 'endpoint' );

			return get_rest_url( null, "$namespace/v1/$base/$endpoint" );
		}

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
