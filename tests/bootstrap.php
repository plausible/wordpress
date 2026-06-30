<?php
/**
 * PHPUnit bootstrap file.
 *
 * @package Plausible_Analytics
 */

$_tests_dir = getenv( 'WP_TESTS_DIR' );

if ( ! $_tests_dir ) {
	$_tests_dir = rtrim( sys_get_temp_dir(), '/\\' ) . '/wordpress-tests-lib';
}

// Forward custom PHPUnit Polyfills configuration to PHPUnit bootstrap file.
$_phpunit_polyfills_path = getenv( 'WP_TESTS_PHPUNIT_POLYFILLS_PATH' );
if ( false !== $_phpunit_polyfills_path ) {
	define( 'WP_TESTS_PHPUNIT_POLYFILLS_PATH', $_phpunit_polyfills_path );
}

if ( ! file_exists( "{$_tests_dir}/includes/functions.php" ) ) {
	echo "Could not find {$_tests_dir}/includes/functions.php, have you run bin/install-wp-tests.sh ?" . PHP_EOL; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
	exit( 1 );
}

// Give access to tests_add_filter() function.
require_once "{$_tests_dir}/includes/functions.php";

/**
 * Manually load the plugin being tested.
 */
function _manually_load_plugin() {
	require dirname( dirname( __FILE__ ) ) . '/plausible-analytics.php';
}

tests_add_filter( 'muplugins_loaded', '_manually_load_plugin' );

/**
 * The function the_block_template_skip_link() is deprecated since WP 6.4.0.
 * It is still hooked to wp_footer in wp-includes/default-filters.php, which causes a deprecation warning.
 * We unhook it here to prevent the warning.
 */
tests_add_filter(
	'init',
	function () {
		remove_action( 'wp_footer', 'the_block_template_skip_link' );
	}
);

// Start up the WP testing environment.
require "{$_tests_dir}/includes/bootstrap.php";
