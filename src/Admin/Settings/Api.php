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
			<div class="plausible-analytics-content">
				<?php echo Helpers::render_quick_actions(); ?>
				<form id="plausible-analytics-settings-form" class="plausible-analytics-form">
				<?php
				foreach ( $this->fields[ $current_tab ] as $tab => $field ) {
					echo call_user_func( [ $this, "render_{$field['type']}_field" ], $field );
				}
				?>
				<div class="plausible-analytics-settings-action-wrap">
					<input type="button" id="plausible-analytics-save-btn" class="plausible-analytics-btn" value="<?php esc_html_e( 'Save Settings', 'plausible-analytics' ); ?>"/>
				</div>
				</form>
			</div>
		</div>
		<?php
	}

	/**
	 * Render Text Field.
	 *
	 * @since  1.3.0
	 * @access public
	 *
	 * @return mixed
	 */
	public function render_text_field( array $field ) {
		ob_start();
		?>
		<label for="">
			<?php echo esc_attr( $field['label'] ); ?>
		</label>
		<input type="text" name="plausible_analytics_settings[<?php echo $field['slug']; ?>]" value="<?php echo $field['value']; ?>" />
		<?php
		return ob_get_clean();
	}

	/**
	 * Render Group Field.
	 *
	 * @since  1.3.0
	 * @access public
	 *
	 * @return mixed
	 */
	public function render_group_field( array $group ) {
		$settings    = Helpers::get_settings();
		$toggle      = ! ! $group['toggle'];
		$fields      = $group['fields'];
		$field_value = ! empty( $settings[ $group['slug'] ] ) ? $settings[ $group['slug'] ] : false;
		$is_checked  = checked( $field_value, true, false );
		ob_start();
		?>
		<div class="plausible-analytics-admin-field">
			<div class="plausible-analytics-admin-field-header">
				<label for="">
					<?php echo esc_attr( $group['label'] ); ?>
				</label>
				<?php if ( $toggle ) { ?>
				<label class="plausible-analytics-switch">
					<input <?php echo $is_checked; ?> class="plausible-analytics-switch-checkbox" name="plausible_analytics_settings[<?php echo $group['slug']; ?>]" value="1" type="checkbox">
					<span class="plausible-analytics-switch-slider"></span>
				</label>
				<?php } ?>
			</div>
			<div class="plausible-analytics-admin-field-body">
				<?php
				if ( ! empty( $fields ) ) {
					foreach ( $fields as $field ) {
						echo call_user_func( [ $this, "render_{$field['type']}_field" ], $field );
					}
				}
				?>
			</div>
			<p class="plausible-analytics-description">
				<?php echo $group['desc']; // Already escaped earlier. ?>
			</p>
		</div>
		<?php
		return ob_get_clean();
	}

	/**
	 * Render Checkbox Field.
	 *
	 * @since  1.3.0
	 * @access public
	 *
	 * @return mixed
	 */
	public function render_checkbox_field( array $field ) {
		ob_start();
		?>
		<span class="plausible-checkbox-list">
			<input type="checkbox" name="plausible_analytics_settings[<?php echo $field['slug']; ?>][]" />
			<?php echo esc_attr( $field['label'] ); ?>
		</span>
		<?php
		return ob_get_clean();
	}
}
