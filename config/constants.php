<?php
// Bailout, if accessed directly.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

// Define plugin version in SemVer format.
if ( ! defined( 'PLAUSIBLE_ANALYTICS_VERSION' ) ) {
	define( 'PLAUSIBLE_ANALYTICS_VERSION', '1.2.5' );
}

// Define plugin root File.
if ( ! defined( 'PLAUSIBLE_ANALYTICS_PLUGIN_FILE' ) ) {
	define( 'PLAUSIBLE_ANALYTICS_PLUGIN_FILE', dirname( dirname( __FILE__ ) ) . '/plausible-analytics.php' );
}

// Define plugin basename.
if ( ! defined( 'PLAUSIBLE_ANALYTICS_PLUGIN_BASENAME' ) ) {
	define( 'PLAUSIBLE_ANALYTICS_PLUGIN_BASENAME', plugin_basename( PLAUSIBLE_ANALYTICS_PLUGIN_FILE ) );
}

// Define plugin directory Path.
if ( ! defined( 'PLAUSIBLE_ANALYTICS_PLUGIN_DIR' ) ) {
	define( 'PLAUSIBLE_ANALYTICS_PLUGIN_DIR', plugin_dir_path( PLAUSIBLE_ANALYTICS_PLUGIN_FILE ) );
}

// Define plugin directory URL.
if ( ! defined( 'PLAUSIBLE_ANALYTICS_PLUGIN_URL' ) ) {
	define( 'PLAUSIBLE_ANALYTICS_PLUGIN_URL', plugin_dir_url( PLAUSIBLE_ANALYTICS_PLUGIN_FILE ) );
}
