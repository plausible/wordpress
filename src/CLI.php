<?php
/**
 * CLI Wrapper for Plausible Analytics
 */

namespace Plausible\Analytics\WP;

class CLI {
	/**
	 * wp plausible create_token (--multisite)
	 *
	 * @param mixed $args
	 * @param mixed $assoc_args
	 *
	 * @return void
	 */
	public function create_token( $args, $assoc_args ) {
		// Extract $args

		// Check if multisite parameter is set.

		// If multisite, loop through each multisite and create token.

		// Otherwise, create token for current site.
	}
}
