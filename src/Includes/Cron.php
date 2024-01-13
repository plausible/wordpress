<?php
/**
 * Plausible Analytics | Cron.
 * @since      1.3.0
 * @package    WordPress
 * @subpackage Plausible Analytics
 */

namespace Plausible\Analytics\WP\Includes;

use WpOrg\Requests\Exception\InvalidArgument;
use Exception;

class Cron {
	/**
	 * Build class
	 * @return void
	 * @throws InvalidArgument
	 * @throws Exception
	 */
	public function __construct() {
		$this->init();
	}

	/**
	 * Run
	 * @return void
	 * @throws InvalidArgument
	 * @throws Exception
	 */
	private function init() {
		$this->download();
	}

	/**
	 * Download the plausible.js file if the Proxy is enabled and downloads it to the uploads directory with an alias.
	 * @return bool
	 * @throws InvalidArgument
	 * @throws Exception
	 */
	private function download() {
		if ( ! Helpers::proxy_enabled() ) {
			return false;
		}

		$remote = Helpers::get_js_url();
		$local  = Helpers::get_js_path();

		return Helpers::download_file( $remote, $local );
	}
}
