<?php
/**
 * Plausible Analytics | Cron.
 *
 * @since      1.3.0
 *
 * @package    WordPress
 * @subpackage Plausible Analytics
 */

namespace Plausible\Analytics\WP\Includes;

use WpOrg\Requests\Exception\InvalidArgument;
use Exception;

class Cron {
	/**
	 * Build class
	 *
	 * @return void
	 * @throws InvalidArgument
	 * @throws Exception
	 */
	public function __construct() {
		$this->init();
	}

	/**
	 * Run
	 *
	 * @return void
	 * @throws InvalidArgument
	 * @throws Exception
	 */
	private function init() {
		$download = $this->download();

		if ( ! wp_doing_cron() && $download ) {
			/**
			 * Only send a success message if this is an AJAX request.
			 */
			wp_send_json_success();
		}
	}

	/**
	 * Download the plausible.js file and download it to the uploads directory with an alias.
	 *
	 * @return bool
	 * @throws InvalidArgument
	 * @throws Exception
	 */
	private function download() {
		$remote = Helpers::get_js_url();
		$local  = Helpers::get_js_path();

		return Helpers::download_file( $remote, $local );
	}
}
