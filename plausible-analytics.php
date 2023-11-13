<?php
/**
 * Plugin Name: Plausible Analytics
 * Plugin URI: https://plausible.io
 * Description: Simple and privacy-friendly alternative to Google Analytics.
 * Author: Plausible.io
 * Author URI: https://plausible.io
 * Version: 1.3.6
 * Text Domain: plausible-analytics
 * Domain Path: /languages
 */

namespace Plausible\Analytics\WP;

// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

// Define plugin version here for convenience.
define( 'PLAUSIBLE_ANALYTICS_VERSION', '1.3.6' );

require_once __DIR__ . '/config/constants.php';

// Automatically loads files used throughout the plugin.
require_once PLAUSIBLE_ANALYTICS_PLUGIN_DIR . 'vendor/autoload.php';

// Initialize the plugin.
$plugin = new Plugin();
$plugin->register();
