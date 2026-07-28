<?php

namespace Plausible\Analytics\WP;

/**
 * Loads and registers plugin functionality through WordPress hooks.
 *
 * @since 1.0.0
 */
final class Plugin {
	/**
	 * Load @see Integrations()
	 *
	 * @return void
	 *
	 * @codeCoverageIgnore
	 */
	public function load_integrations() {
		$this->load_service( Integrations::class );
	}

	/**
	 * Instantiates a service class without taking down the site when its file can't be loaded.
	 *
	 * WordPress' updater deletes the plugin directory before copying the new version into place, so a
	 * request landing in that window finds part of our source tree missing. Because
	 * @see   register_services() runs on plugins_loaded for every request, an unguarded `new` turns that
	 * into a fatal error on the front end instead of a single disabled feature.
	 *
	 * Note that `::class` is resolved at compile time and doesn't trigger the autoloader, so passing a
	 * missing class here is safe.
	 *
	 * @since 2.6.1
	 *
	 * @param string $class_name Fully qualified class name.
	 *
	 * @return object|null Null when the class couldn't be loaded.
	 */
	private function load_service( $class_name ) {
		if ( ! class_exists( $class_name ) ) {
			$this->log_missing_service( $class_name );

			return null;
		}

		return new $class_name();
	}

	/**
	 * Logs a service that couldn't be loaded by @see load_service().
	 *
	 * Only logged when WP_DEBUG is enabled: during a plugin update this is expected and transient, and
	 * we don't want to fill production logs with it.
	 *
	 * @since 2.6.1
	 *
	 * @param string $class_name Fully qualified class name.
	 *
	 * @return void
	 *
	 * @codeCoverageIgnore
	 */
	private function log_missing_service( $class_name ) {
		if ( ! defined( 'WP_DEBUG' ) || ! WP_DEBUG ) {
			return;
		}

		// phpcs:ignore WordPress.PHP.DevelopmentFunctions.error_log_error_log
		error_log( sprintf( 'Plausible Analytics: %s could not be loaded and was skipped.', $class_name ) );
	}

	/**
	 * Loads the plugin's translated strings.
	 *
	 * @since  1.0.0
	 * @access public
	 * @return void
	 *
	 * @codeCoverageIgnore
	 */
	public function load_plugin_textdomain() {
		load_plugin_textdomain(
			'plausible-analytics',
			false,
			dirname( plugin_basename( PLAUSIBLE_ANALYTICS_PLUGIN_FILE ) ) . '/languages/'
		);
	}

	/**
	 * Load @see Admin\Provisioning()
	 *
	 * @return void
	 *
	 * @codeCoverageIgnore
	 */
	public function load_provisioning() {
		$this->load_service( Admin\Provisioning::class );
		$this->load_service( Admin\Provisioning\Integrations::class );
	}

	/**
	 * Load @see Admin\Settings\Page()
	 *
	 * @return void
	 *
	 * @codeCoverageIgnore
	 */
	public function load_settings() {
		$this->load_service( Admin\Settings\Page::class );
	}

	/**
	 * Registers functionality with WordPress hooks.
	 *
	 * @since  1.0.0
	 * @access public
	 * @return void
	 */
	public function register() {
		$this->setup();

		// Register services used throughout the plugin. (WP Rocket runs at priority 10)
		add_action( 'plugins_loaded', [ $this, 'register_services' ], 9 );

		// Load text domain.
		add_action( 'init', [ $this, 'load_plugin_textdomain' ], 1000 );
	}

	/**
	 * Register plugin (de)activation hooks and cron job.
	 *
	 * @return void
	 */
	public function setup() {
		$this->load_service( Setup::class );
	}

	/**
	 * Registers the individual services of the plugin.
	 *
	 * @since  1.0.0
	 * @access public
	 * @return void
	 */
	public function register_services() {
		if ( is_admin() ) {
			add_action( 'init', [ $this, 'load_settings' ] );
			add_action( 'init', [ $this, 'load_provisioning' ] );

			$this->load_service( Admin\Upgrades::class );
			$this->load_service( Admin\Filters::class );
			$this->load_service( Admin\Actions::class );
			$this->load_service( Admin\Module::class );
			$this->load_service( Admin\PrivacyPolicy::class );
		}

		add_action( 'init', [ $this, 'load_integrations' ] );

		$this->load_service( AdminBar::class );
		$this->load_service( Assets::class );
		$this->load_service( Ajax::class );
		$this->load_service( Compatibility::class );
		$this->load_service( InitOptions::class );
		$this->load_service( Proxy::class );
		$this->load_service( Verification::class );
	}
}
