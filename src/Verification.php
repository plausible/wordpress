<?php
/**
 * Plausible Analytics | Actions.
 * @since      1.0.0
 * @package    WordPress
 * @subpackage Plausible Analytics
 */

namespace Plausible\Analytics\WP;

class Verification {
	/**
	 * Build class.
	 * @return void
	 * @since  1.0.0
	 */
	public function __construct( $init = true ) {
		if ( $init ) {
			$this->init();
		}
	}

	/**
	 * Plugin actions/hooks
	 * @return void
	 */
	private function init() {
		add_action( 'wp_head', [ $this, 'maybe_insert_version_meta_tag' ] );
	}

	/**
	 * This <meta> tag "tells" the Plausible API which version of the plugin is used, to allow tailored error messages,
	 * specific to the plugin version.
	 *
	 * @return void
	 */
	public function maybe_insert_version_meta_tag() {
		$running_verification = array_key_exists( 'plausible_verification', $_GET );

		if ( ! $running_verification ) {
			return;
		}

		$version = $this->get_plugin_version();

		if ( $version ) {
			echo "<meta name='plausible-analytics-version' content='$version' />\n";
		}
	}

	/**
	 * Retrieves the plugin's current version.
	 */
	private function get_plugin_version() {
		if ( ! function_exists( 'get_plugin_data' ) ) {
			require_once ABSPATH . 'wp-admin/includes/plugin.php'; // @codeCoverageIgnore
		}

		static $data = null;

		if ( $data === null ) {
			$data = get_plugin_data( PLAUSIBLE_ANALYTICS_PLUGIN_FILE );
		}

		return $data['Version'] ?? '';
	}
}
