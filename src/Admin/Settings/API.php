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
					<button
						id="plausible-analytics-save-btn"
						class="plausible-analytics-btn plausible-analytics-save-btn"
						data-default-text="<?php esc_attr_e( 'Save Changes', 'plausible-analytics' ); ?>"
						data-saved-text="<?php esc_attr_e( 'Saved!', 'plausible-analytics' ); ?>"
					>
						<span><?php esc_html_e( 'Save Changes', 'plausible-analytics' ); ?></span>
						<span class="plausible-analytics-spinner">
							<div class="plausible-analytics-spinner--bounce-1"></div>
							<div class="plausible-analytics-spinner--bounce-2"></div>
						</span>
					</button>
					<?php wp_nonce_field( 'plausible-analytics-settings-roadblock', 'roadblock' ); ?>
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
	 * @return string
	 */
	public function render_text_field( array $field ) {
		ob_start();
		$value = ! empty( $field['value'] ) ? $field['value'] : '';
		?>
		<label for="<?php echo $field['slug']; ?>">
			<?php echo esc_attr( $field['label'] ); ?>
		</label>
		<input id="<?php echo $field['slug']; ?>" type="text" name="plausible_analytics_settings[<?php echo $field['slug']; ?>]" value="<?php echo $value; ?>" />
		<?php
		return ob_get_clean();
	}

	/**
	 * Render Group Field.
	 *
	 * @since  1.3.0
	 * @access public
	 *
	 * @return string
	 */
	public function render_group_field( array $group ) {
		$settings    = Helpers::get_settings();
		$toggle      = $group['toggle'];
		$fields      = $group['fields'];
		$field_value = ! empty( $settings[ $group['slug'] ] ) ? $settings[ $group['slug'] ] : false;
		$is_checked  = checked( $field_value, true, false );
		ob_start();
		?>
		<div class="plausible-analytics-admin-field">
			<div class="plausible-analytics-admin-field-header">
				<label for="">
					<?php echo $group['label']; ?>
				</label>
				<?php if ( $toggle === true ) { ?>
				<label class="plausible-analytics-switch">
					<input <?php echo $is_checked; ?> class="plausible-analytics-switch-checkbox" name="plausible_analytics_settings[<?php echo $group['slug']; ?>]" value="1" type="checkbox">
					<span class="plausible-analytics-switch-slider"></span>
				</label>
				<?php } elseif ( is_array( $toggle ) ) { ?>
					<a target="_blank" class="plausible-analytics-link" href="<?php echo $toggle['anchor']; ?>">
						<?php echo $toggle['label']; ?>
					</a>
				<?php } ?>
			</div>
			<div class="plausible-analytics-admin-field-body">
				<?php
				if ( ! empty( $fields ) ) {
					foreach ( $fields as $field ) {
						echo call_user_func( [ $this, "render_{$field['type']}_field" ], $field ) . '<br/>';
					}
				}
				?>
			</div>
			<div class="plausible-analytics-description">
				<?php echo wp_kses_post( $group['desc'] ); ?>
			</div>
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
	 * @return string
	 */
	public function render_checkbox_field( array $field ) {
		ob_start();
		$value    = ! empty( $field['value'] ) ? $field['value'] : 'on';
		$settings = Helpers::get_settings();
		?>
		<span class="plausible-checkbox-list">
			<input
				id="<?php echo $field['slug']; ?>"
				type="checkbox"
				name="plausible_analytics_settings[<?php echo esc_attr( $field['slug'] ); ?>][]"
				value="<?php echo esc_html( $value ); ?>"
				<?php
				! empty( $settings[ $field['slug'] ] ) ?
					checked( in_array( $value, $settings[ $field['slug'] ], true ), true ) :
					'';
				?>
			/>
			<label for="<?php echo $field['slug']; ?>"><?php echo $field['label']; ?></label>
			<?php if ( ! empty( $field['docs'] ) ) { ?>
				- <a href="<?php echo $field['docs']; ?>"><?php echo $field['docs_label']; ?></a>
			<?php } ?>
		</span>
		<?php
		return ob_get_clean();
	}
}
