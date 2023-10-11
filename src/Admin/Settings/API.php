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
	 * @return void
	 * @since  1.3.0
	 * @access public
	 *
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
						$type = $field['type'] ?? '';

						if ( $type ) {
							echo call_user_func( [ $this, "render_{$type}_field" ], $field );
						}
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
	 * @return string
	 * @since  1.3.0
	 * @access public
	 *
	 */
	public function render_text_field( array $field ) {
		ob_start();
		$value       = ! empty( $field['value'] ) ? $field['value'] : '';
		$placeholder = ! empty( $field['placeholder'] ) ? $field['placeholder'] : '';
		$disabled    = ! empty( $field['disabled'] ) ? 'disabled' : '';
		?>
		<span class="plausible-text-field">
			<label for="<?php echo $field['slug']; ?>"><?php echo esc_attr( $field['label'] ); ?></label>
			<input id="<?php echo $field['slug']; ?>" placeholder="<?php echo $placeholder; ?>" type="text"
				   name="plausible_analytics_settings[<?php echo $field['slug']; ?>]"
				   value="<?php echo $value; ?>" <?php echo $disabled; ?> />
		</span>
		<?php
		return ob_get_clean();
	}

	/**
	 * Render Group Field.
	 *
	 * @return string
	 * @since  1.3.0
	 * @access public
	 *
	 */
	public function render_group_field( array $group ) {
		$toggle = $group['toggle'] ?? [];
		$fields = $group['fields'];
		ob_start();
		?>
		<div class="plausible-analytics-admin-field <?php echo str_replace( '_', '-', $group['slug'] ); ?>">
			<div class="plausible-analytics-admin-field-header">
				<label for="">
					<?php echo $group['label']; ?>
				</label>
				<?php if ( ! empty( $toggle ) && is_array( $toggle ) ) : ?>
					<a target="_blank" class="plausible-analytics-link" href="<?php echo $toggle['anchor']; ?>">
						<?php echo $toggle['label']; ?>
					</a>
				<?php endif; ?>
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
	 * @return string
	 * @since  1.3.0
	 * @access public
	 *
	 */
	public function render_checkbox_field( array $field ) {
		ob_start();
		$value    = ! empty( $field['value'] ) ? $field['value'] : 'on';
		$settings = Helpers::get_settings();
		$slug     = ! empty( $settings[ $field['slug'] ] ) ? $settings[ $field['slug'] ] : '';
		$id       = $field['slug'] . '_' . str_replace( '-', '_', sanitize_title( $field['label'] ) );
		$checked  = ! empty( $field['checked'] ) ? 'checked="checked"' : ( is_array( $slug ) ? checked( $value, in_array( $value, $slug, false ) ? $value : false, false ) : checked( $value, $slug, false ) );
		$disabled = ! empty( $field['disabled'] ) ? 'disabled' : '';

		?>
		<span class="plausible-checkbox-list">
			<input id="<?php echo $id; ?>" type="checkbox"
				   name="plausible_analytics_settings[<?php echo esc_attr( $field['slug'] ); ?>][]"
				   value="<?php echo esc_html( $value ); ?>" <?php echo $checked; ?> <?php echo $disabled; ?> />
			<?php // This trick'll make our option always show up in $_POST. Even when unchecked. ?>
			<input id="<?php echo $id; ?>" type="hidden"
				   name="plausible_analytics_settings[<?php echo esc_attr( $field['slug'] ); ?>][]" value="0"/>
			<label for="<?php echo $id; ?>"><?php echo $field['label']; ?></label>
			<?php if ( ! empty( $field['docs'] ) ) { ?>
				- <a target="_blank" href="<?php echo $field['docs']; ?>"><?php echo $field['docs_label']; ?></a>
			<?php } ?>
		</span>
		<?php
		return ob_get_clean();
	}

	/**
	 * Render textarea field.
	 *
	 * @param array $field
	 *
	 * @return string|false
	 * @since 1.2.5
	 * @access public
	 *
	 */
	public function render_textarea_field( array $field ) {
		ob_start();
		$value       = ! empty( $field['value'] ) ? $field['value'] : '';
		$placeholder = ! empty( $field['placeholder'] ) ? $field['placeholder'] : '';
		?>
		<label for="<?php echo esc_attr( $field['slug'] ); ?>">
			<?php echo esc_attr( $field['label'] ); ?>
		</label>
		<textarea rows="5" id="<?php echo esc_attr( $field['slug'] ); ?>"
				  placeholder="<?php echo esc_attr( $placeholder ); ?>"
				  name="plausible_analytics_settings[<?php echo esc_attr( $field['slug'] ); ?>]"><?php echo $value; ?></textarea>
		<?php
		return ob_get_clean();
	}

	/**
	 * Render just the label, and allow insertion of anything using the hook beside it.
	 *
	 * @param array $field
	 *
	 * @return string|false
	 * @since 1.3.0
	 *
	 */
	public function render_hook_field( array $field ) {
		ob_start();
		?>
		<div class="plausible-hook">
			<div class="<?php echo esc_attr( str_replace( '_', '-', $field['slug'] ) ); ?>">
				<?php do_action( 'plausible_analytics_settings_' . $field['slug'], $field['slug'] ); ?>
			</div>
		</div>
		<?php

		return trim( ob_get_clean() );
	}
}
