<?php
/**
 * Plausible Analytics | Settings API.
 *
 * @since 1.3.0
 *
 * @package    WordPress
 * @subpackage Plausible Analytics
 */

namespace Plausible\Analytics\WP\Admin\Settings;

use Plausible\Analytics\WP\Includes\Helpers;

// Bailout, if accessed directly.
if ( ! defined( 'ABSPATH' ) ) {
	wp_die( 'Cheat\'in huh?' );
}

class API {

	/**
	 * Admin Setting Fields.
	 *
	 * @since  1.3.0
	 * @access public
	 *
	 * @var array
	 */
	public $fields = [];

	/**
	 * Constructor.
	 *
	 * @since  1.3.0
	 * @access public
	 *
	 * @return void
	 */
	public function __construct() {

	}

	/**
	 * Render Fields.
	 *
	 * @since  1.3.0
	 * @access public
	 *
	 * @return void
	 */
	public function settings_page() {
		$current_tab = ! empty( $_GET['tab'] ) ? $_GET['tab'] : 'general';
		?>
		<div class="wrap plausible-analytics-wrap">
			<div class="plausible-analytics-content-left">
				<form id="plausible-analytics-settings-form" class="plausible-analytics-form">
				<?php
				foreach ( $this->fields[ $current_tab ] as $tab => $field ) {
					call_user_func( [ $this, "render_{$field['type']}_field" ], $field );
				}
				?>
				</form>
			</div>
			<div class="plausible-analytics-content-right">

			</div>
		</div>
		<?php
	}

	public function render_text_field( array $field ) {
		$toggle = ! ! $field['toggle'];
		ob_start();
		?>
		<div class="plausible-analytics-admin-field">
			<div class="plausible-analytics-admin-field-header">
				<label for="">
					<?php echo esc_attr( $field['label'] ); ?>
				</label>
				<?php if ( $toggle ) { ?>
				<label class="plausible-analytics-switch">
					<input checked="checked" class="plausible-analytics-switch-checkbox" name="plausible_analytics_settings[<?php echo $field['slug']; ?>]" value="1" type="checkbox">
					<span class="plausible-analytics-switch-slider"></span>
				</label>
				<?php } ?>
			</div>
			<p class="plausible-analytics-description">
				<?php echo $field['desc']; // Already escaped earlier. ?>
			</p>
		</div>
		<?php
		echo ob_get_clean();
	}
}
