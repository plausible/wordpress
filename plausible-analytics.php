<?php
/**
 * Plugin Name: Plausible Analytics
 * Plugin URI: https://plausible.io
 * Description: Simple and privacy-friendly alternative to Google Analytics.
 * Author: PlausibleHQ
 * Author URI: https://plausible.io
 * Version: 1.0.1
 * Text Domain: plausible-analytics
 * Domain Path: /languages
 *
 * A Tribute to Open Source:
 *
 * "Open source software is software that can be freely used, changed, and shared (in modified or unmodified form) by
 * anyone. Open source software is made by many people, and distributed under licenses that comply with the Open Source
 * Definition."
 *
 * -- The Open Source Initiative
 *
 * Plausible Analytics is a tribute to the spirit and philosophy of Open Source. We at PlausibleHQ gladly embrace the Open Source
 * philosophy both in how Plausible Analytics itself was developed, and how we hope to see others build more from our code base.
 *
 * Plausible Analytics would not have been possible without the tireless efforts of WordPress and the surrounding Open Source projects
 * and their talented developers. Thank you all for your contribution to WordPress.
 *
 * - The Plausible Analytics Team
 */

namespace Plausible\Analytics\WP;

// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

require_once __DIR__ . '/config/constants.php';

// Automatically loads files used throughout the plugin.
require_once PLAUSIBLE_ANALYTICS_PLUGIN_DIR . 'vendor/autoload.php';

// Initialize the plugin.
$plugin = new Plugin();
$plugin->register();
