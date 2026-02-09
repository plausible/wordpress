<?php
/**
 * Plausible Analytics | Upgrades
 *
 * @since      1.3.0
 * @package    WordPress
 * @subpackage Plausible Analytics
 */

namespace Plausible\Analytics\WP\Admin;

use Exception;
use Plausible\Analytics\WP\Admin\Provisioning\Integrations;
use Plausible\Analytics\WP\Client;
use Plausible\Analytics\WP\Cron;
use Plausible\Analytics\WP\EnhancedMeasurements;
use Plausible\Analytics\WP\Helpers;
use Plausible\Analytics\WP\Setup;

/**
 * Class Upgrades
 *
 * @since 1.3.0
 */
class Upgrades {
	/**
	 * Constructor for Upgrades.
	 *
	 * @return void
	 * @since  1.3.0
	 * @access public
	 */
	public function __construct() {
		add_action( 'init', [ $this, 'run' ] );
	}

	/**
	 * Register routines for upgrades.
	 * This is intended for automatic upgrade routines having less resource intensive tasks.
	 *
	 * @return void
	 *
	 * @throws Exception
	 *
	 * @codeCoverageIgnore
	 * @since  1.3.0
	 * @access public
	 */
	public function run() {
		$plausible_analytics_version = get_option( 'plausible_analytics_version' );

		// If version doesn't exist, then consider it `1.0.0`.
		if ( ! $plausible_analytics_version ) {
			$plausible_analytics_version = '1.0.0';
		}

		if ( version_compare( $plausible_analytics_version, '1.2.5', '<' ) ) {
			$this->upgrade_to_125();
		}

		if ( version_compare( $plausible_analytics_version, '1.2.6', '<' ) ) {
			$this->upgrade_to_126();
		}

		if ( version_compare( $plausible_analytics_version, '1.3.2', '<' ) ) {
			$this->upgrade_to_132();
		}

		if ( version_compare( $plausible_analytics_version, '2.0.0', '<' ) ) {
			$this->upgrade_to_200();
		}

		if ( version_compare( $plausible_analytics_version, '2.0.3', '<' ) ) {
			$this->upgrade_to_203();
		}

		if ( version_compare( $plausible_analytics_version, '2.1.0', '<' ) ) {
			$this->upgrade_to_210();
		}

		if ( version_compare( $plausible_analytics_version, '2.3.0', '<' ) ) {
			$this->upgrade_to_230();
		}

		if ( version_compare( $plausible_analytics_version, '2.3.1', '<' ) ) {
			$this->upgrade_to_231();
		}

		if ( version_compare( $plausible_analytics_version, '2.5.0', '<' ) ) {
			$this->upgrade_to_250();
		}

		if ( version_compare( $plausible_analytics_version, '2.5.1', '<' ) ) {
			$this->upgrade_to_251();
		}

		if ( version_compare( $plausible_analytics_version, '2.5.3', '<' ) ) {
			$this->upgrade_to_253();
		}

		if ( version_compare( $plausible_analytics_version, '2.5.4', '<' ) ) {
			$this->upgrade_to_254();
		}

		// Add required upgrade routines for future versions here.
	}

	/**
	 * Upgrade routine for 1.2.5
	 * Cleans Custom Domain related options from database, as it was removed in this version.
	 *
	 * @return void
	 * @codeCoverageIgnore
	 * @since  1.2.5
	 * @access public
	 */
	public function upgrade_to_125() {
		$old_settings = Helpers::get_settings();
		$new_settings = $old_settings;

		if ( isset( $old_settings['custom_domain_prefix'] ) ) {
			unset( $new_settings['custom_domain_prefix'] );
		}

		if ( isset( $old_settings['custom_domain'] ) ) {
			unset( $new_settings['custom_domain'] );
		}

		if ( isset( $old_settings['is_custom_domain'] ) ) {
			unset( $new_settings['is_custom_domain'] );
		}

		if ( ! empty( $old_settings['track_administrator'] ) && $old_settings['track_administrator'] === 'true' ) {
			$new_settings['tracked_user_roles'] = [ 'administrator' ];
		}

		update_option( 'plausible_analytics_settings', $new_settings );

		update_option( 'plausible_analytics_version', '1.2.5' );
	}

	/**
	 * Get rid of the previous "example.com" default for self_hosted_domain.
	 *
	 * @return void
	 * @codeCoverageIgnore
	 * @since 1.2.6
	 */
	public function upgrade_to_126() {
		$old_settings = Helpers::get_settings();
		$new_settings = $old_settings;

		if ( ! empty( $old_settings['self_hosted_domain'] ) && strpos( $old_settings['self_hosted_domain'], 'example.com' ) !== false ) {
			Helpers::update_setting( 'self_hosted_domain', '' );
		}

		update_option( 'plausible_analytics_version', '1.2.6' );
	}

	/**
	 * Upgrade to 1.3.2
	 * - Updates the Proxy Resource, Cache URL to be protocol relative.
	 *
	 * @return void
	 * @throws Exception
	 * @codeCoverageIgnore
	 */
	private function upgrade_to_132() {
		$proxy_resources = Helpers::get_proxy_resources();

		$proxy_resources['cache_url'] = str_replace( [ 'https:', 'http:' ], '', $proxy_resources['cache_url'] );

		update_option( 'plausible_analytics_proxy_resources', $proxy_resources );

		update_option( 'plausible_analytics_version', '1.3.2' );
	}

	/**
	 * Cleans the settings of the old, unneeded sub-arrays for settings.
	 *
	 * @return void
	 * @codeCoverageIgnore
	 */
	private function upgrade_to_200() {
		$settings     = Helpers::get_settings();
		$toggle_lists = [
			'enhanced_measurements',
			'tracked_user_roles',
			'expand_dashboard_access',
		];

		foreach ( $settings as $option_name => $option_value ) {
			if ( ! is_array( $option_value ) ) {
				continue;
			}

			// For toggle lists, we only need to clean out the no longer needed zero values.
			if ( in_array( $option_name, $toggle_lists ) ) {
				$settings[ $option_name ] = array_filter( $option_value );

				continue;
			}

			// Single toggle.
			$clean_value = array_filter( $option_value );

			// Disabled options are now stored as (more sensible) empty strings instead of empty arrays.
			if ( empty( $clean_value ) ) {
				$settings[ $option_name ] = '';

				continue;
			}

			// Any other value will now default to 'on'.
			$settings[ $option_name ] = 'on';
		}

		/**
		 * Migrate the shared link option for self hosters who use it.
		 */
		if ( ! empty( $settings['self_hosted_domain'] ) && ! empty( $settings['shared_link'] ) ) {
			Helpers::update_setting( 'self_hosted_shared_link', $settings['shared_link'] );
			Helpers::update_setting( 'shared_link', '' );
		}

		update_option( 'plausible_analytics_version', '2.0.0' );

		// No longer need this db entry.
		delete_option( 'plausible_analytics_is_default_settings_saved' );

		// We no longer need to store transient to keep notices dismissed.
		delete_transient( 'plausible_analytics_module_install_failed_notice_dismissed' );
		delete_transient( 'plausible_analytics_proxy_test_failed_notice_dismissed' );
		delete_transient( 'plausible_analytics_notice' );
	}

	/**
	 * Makes sure the View Stats option is enabled for users that previously set a shared link.
	 *
	 * @return void
	 * @codeCoverageIgnore
	 */
	private function upgrade_to_203() {
		$settings = Helpers::get_settings();

		if ( ! empty( $settings['shared_link'] ) ) {
			Helpers::update_setting( 'enable_analytics_dashboard', 'on' );
		}

		update_option( 'plausible_analytics_version', '2.0.3' );
	}

	/**
	 * v2.0.8 and older contained a bug that caused the Enhanced Measurement option to not be an array in some cases.
	 *
	 * @return void
	 * @codeCoverageIgnore
	 */
	public function upgrade_to_210() {
		$settings = Helpers::get_settings();

		if ( ! is_array( $settings['enhanced_measurements'] ) ) {
			Helpers::update_setting( 'enhanced_measurements', [] );
		}

		update_option( 'plausible_analytics_version', '2.1.0' );
	}

	/**
	 * If EDD is active and Ecommerce is enabled, create goals after updating the plugin.
	 *
	 * @return void
	 *
	 * @codeCoverageIgnore because all we'd be doing is testing the Plugins API.
	 * @since              v2.3.0
	 *
	 */
	public function upgrade_to_230() {
		$settings = Helpers::get_settings();

		if ( EnhancedMeasurements::is_enabled( EnhancedMeasurements::ECOMMERCE_REVENUE ) ) {
			$edd_provisioning = new Provisioning\Integrations\EDD( new Integrations() );
			$provisioning     = new Provisioning();

			// No token entered.
			if ( ! $provisioning->client instanceof Client ) {
				return;
			}

			$provisioning->maybe_create_custom_properties( [], $settings );
			$edd_provisioning->maybe_create_edd_funnel( [], $settings );
		}

		update_option( 'plausible_analytics_version', '2.3.0' );
	}

	/**
	 * Make sure the cron event is scheduled. If it's already scheduled or the Proxy isn't enabled, it'll bail.
	 *
	 * @return void
	 */
	public function upgrade_to_231() {
		$setup = new Setup();

		$setup->activate_cron();

		update_option( 'plausible_analytics_version', '2.3.1' );
	}

	/**
	 * If Search Queries is enabled, make sure the custom properties are created after updating.
	 *
	 * @return void
	 *
	 * @codeCoverageIgnore because all we'd be doing is testing the Plugins API.
	 */
	public function upgrade_to_250() {
		$settings = Helpers::get_settings();

		if ( EnhancedMeasurements::is_enabled( EnhancedMeasurements::SEARCH_QUERIES ) ) {
			$provisioning = new Provisioning();

			// No token entered.
			if ( ! $provisioning->client instanceof Client ) {
				return;
			}

			$provisioning->maybe_create_custom_properties( [], $settings );
		}

		update_option( 'plausible_analytics_version', '2.5.0' );
	}

	/**
	 * Make sure the configuration on Plausible's end matches our configuration.
	 *
	 * @return void
	 *
	 * @codeCoverageIgnore Because Provisioning is tested elsewhere.
	 */
	public function upgrade_to_251() {
		$provisioning = new Provisioning();

		if ( ! $provisioning->client instanceof Client ) {
			return;
		}

		$settings = Helpers::get_settings();

		$provisioning->update_tracker_script_config( null, $settings );

		// This makes sure the new JS file is downloaded.
		new Cron();

		update_option( 'plausible_analytics_version', '2.5.1' );
	}

	/**
	 * Show an admin-wide notice to CE users that haven't entered an API token yet.
	 *
	 * @return void
	 */
	public function upgrade_to_253() {
		$self_hosted_domain = Helpers::get_settings()['self_hosted_domain'];
		$api_token          = Helpers::get_settings()['api_token'];

		// Not a CE user or a CE user already using the Plugins API.
		if ( empty( $self_hosted_domain ) || ! empty( $api_token ) ) {
			update_option( 'plausible_analytics_version', '2.5.3' );

			return;
		}

		add_action( 'admin_notices', [ $this, 'show_ce_api_token_notice' ] );
	}

	/**
	 * Show an admin-wide notice to CE users that haven't entered an API token yet.
	 *
	 * @return void
	 */
	public function upgrade_to_254() {
		$self_hosted_domain = Helpers::get_settings()['self_hosted_domain'];
		$api_token          = Helpers::get_settings()['api_token'];

		// This user apparently hasn't entered an API token yet.
		if ( ! empty( $api_token ) && empty ( $self_hosted_domain ) ) {
			update_option( 'plausible_analytics_version', '2.5.4' );

			return;
		}

		add_action( 'admin_notices', [ $this, 'show_api_token_notice' ] );
	}

	/**
	 * Display a notice to CE users that haven't entered an API token yet.
	 *
	 * @return void
	 */
	public function show_ce_api_token_notice() {
		$url = admin_url( 'options-general.php?page=plausible_analytics' );

		?>
		<div class="notice notice-warning">
			<p><?php echo sprintf( __( 'A plugin token for Plausible is required. Please create one from the <a href="%s">Settings screen</a> and upgrade Plausible CE if necessary.', 'plausible-analytics' ), $url ); ?></p>
		</div>
		<?php
	}

	/**
	 * Display a notice to CE users that haven't entered an API token yet.
	 *
	 * @return void
	 */
	public function show_api_token_notice() {
		$url = admin_url( 'options-general.php?page=plausible_analytics' );

		?>
		<div class="notice notice-warning">
			<p><?php echo sprintf( __( 'Almost there! Stats tracking requires a Plausible plugin token. Create one on the <a href="%s">Settings screen</a>, and press Connect to complete setup.', 'plausible-analytics' ), $url ); ?></p>
		</div>
		<?php
	}
}
