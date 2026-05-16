<?php
/**
 * Plausible Analytics | Uninstall script.
 *
 * @since      1.3.0
 *
 * @package    WordPress
 * @subpackage Plausible Analytics
 */

defined( 'WP_UNINSTALL_PLUGIN' ) || exit;

require_once( dirname( __FILE__ ) . '/src/Uninstall.php' );

$plausible_uninstaller = new \Plausible\Analytics\WP\Uninstall();
$plausible_uninstaller->run();
