<?php

namespace Plausible\Analytics\WP;

/**
 * Loads and registers plugin functionality through WordPress hooks.
 *
 * @since 1.0.0
 */
final class Plugin {
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
		new Setup();
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

			new Admin\Upgrades();
			new Admin\Filters();
			new Admin\Actions();
			new Admin\Module();
		}

		add_action( 'init', [ $this, 'load_integrations' ] );

		new AdminBar();
		new Assets();
		new Ajax();
		new Compatibility();
		new InitOptions();
		new Proxy();
		new Verification();
	}

	/**
	 * Load @see Admin\Settings\Page()
	 *
	 * @return void
	 *
	 * @codeCoverageIgnore
	 */
	public function load_settings() {
		new Admin\Settings\Page();
	}

	/**
	 * Load @see Admin\Provisioning()
	 *
	 * @return void
	 *
	 * @codeCoverageIgnore
	 */
	public function load_provisioning() {
		new Admin\Provisioning();
		new Admin\Provisioning\Integrations();
	}

	/**
	 * Load @see Integrations()
	 *
	 * @return void
	 *
	 * @codeCoverageIgnore
	 */
	public function load_integrations() {
		new Integrations();
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
}
