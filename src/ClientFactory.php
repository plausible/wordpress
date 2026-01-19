<?php

namespace Plausible\Analytics\WP;

use Plausible\Analytics\WP\Admin\Messages;

class ClientFactory {
	/**
	 * @var string $token
	 */
	private $token;

	/**
	 * Setup basic authorization.
	 *
	 * @param string $token Allows to specify the token, e.g. when it's not stored in the DB yet.
	 */
	public function __construct( $token = '' ) {
		$this->token = $token;
	}

	/**
	 * Loads the Client class if all conditions are met.
	 *
	 * @return false|Client
	 */
	public function build() {
		/**
		 * cURL or allow_url_fopen ini setting is required for GuzzleHttp to function properly.
		 */
		if ( ! extension_loaded( 'curl' ) && ! ini_get( 'allow_url_fopen' ) ) {
			add_action( 'init', [ $this, 'add_curl_error' ] ); // @codeCoverageIgnore

			return false; // @codeCoverageIgnore
		}

		if ( ! $this->token ) {
			$this->token = Helpers::get_settings()['api_token'];
		}

		if ( ! $this->token ) {
			return false;
		}

		return new Client( $this->token );
	}

	/**
	 * Show an error on the settings screen if cURL isn't enabled on this machine.
	 *
	 * @return void
	 *
	 * @codeCoverageIgnore
	 */
	public function add_curl_error() {
		Messages::set_error(
			__(
				'cURL is not enabled on this server, which means API provisioning will not work. Please contact your hosting provider to enable the cURL module or <code>allow_url_fopen</code>.',
				'plausible-analytics'
			)
		);
	}
}
