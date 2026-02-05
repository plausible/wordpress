<?php
/**
 * Plausible Analytics | Cron.
 *
 * @since      1.3.0
 * @package    WordPress
 * @subpackage Plausible Analytics
 */

namespace Plausible\Analytics\WP;

use WpOrg\Requests\Exception\InvalidArgument;
use Exception;

class Cron {
	/**
	 * Cron job handle
	 *
	 * @var string
	 */
	const TASK_NAME = 'plausible_analytics_update_js';

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
		$this->maybe_download();
	}

	/**
	 * Download the plausible.js file if the Proxy is enabled and downloads it to the uploads directory with an alias.
	 *
	 * @return bool
	 * @throws InvalidArgument
	 * @throws Exception
	 */
	private function maybe_download() {
		if ( ! Helpers::proxy_enabled() ) {
			return false;
		}

		$remote = Helpers::get_js_url();
		$local  = Helpers::get_js_path();

		return $this->download_file( $remote, $local );
	}

	/**
	 * Downloads a remote file to this server.
	 *
	 * @param string $local_file Absolute path to where to store the $remote_file.
	 * @param string $remote_file Full URL to file to download.
	 *
	 * @return bool True when successful. False if it fails.
	 * @throws Exception
	 * @throws InvalidArgument
	 *
	 * @since 1.3.0
	 */
	private function download_file( $remote_file, $local_file ) {
		$file_contents = wp_remote_get( $remote_file );

		if ( is_wp_error( $file_contents ) ) {
			// TODO: add error handling?
			return false; // @codeCoverageIgnore
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
}
