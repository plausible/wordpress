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
				<?php echo Helpers::render_quick_actions(); ?>
				<form id="plausible-analytics-settings-form" class="plausible-analytics-form">
				<?php
				foreach ( $this->fields[ $current_tab ] as $tab => $field ) {
					echo call_user_func( [ $this, "render_{$field['type']}_field" ], $field );
				}
				?>
				</form>
			</div>
			<div class="plausible-analytics-content-right">
				<div class="plausible-analytics-widget">
					<a href="" target="_blank" title="<?php esc_html_e( 'Check our documentation', 'plausible-analytics' ); ?>">
						<svg xmlns="http://www.w3.org/2000/svg" xmlns:xlink="http://www.w3.org/1999/xlink" width="350" height="100" viewBox="0 0 500 150"><defs><clipPath id="b"><rect width="500" height="150"/></clipPath></defs><g id="a" clip-path="url(#b)"><rect width="500" height="150" fill="#ede9fe"/><g fill="#f5f3ff" stroke="#5850ec" stroke-linecap="round" stroke-linejoin="bevel" stroke-width="4"><rect width="500" height="150" stroke="none"/><rect x="2" y="2" width="496" height="146" fill="none"/></g><text transform="translate(199 113)" font-size="46" font-family="Muli-ExtraBold, Muli" font-weight="800"><tspan x="-171.971" y="0">Documentation</tspan></text><text transform="translate(27 52)" font-size="20" font-family="Muli-Bold, Muli" font-weight="700"><tspan x="0" y="0">CHECK OUR</tspan></text><g transform="matrix(0.819, -0.574, 0.574, 0.819, 343.341, 105.549)"><g transform="translate(44.944)"><g transform="translate(0)"><path d="M147.426,31.026,117.149.749A2.556,2.556,0,0,0,115.342,0H57.363A12.433,12.433,0,0,0,44.944,12.42V112.794a12.434,12.434,0,0,0,12.419,12.419h78.392a12.433,12.433,0,0,0,12.419-12.419V32.833A2.558,2.558,0,0,0,147.426,31.026ZM117.9,8.726,139.45,30.277H125.207a7.308,7.308,0,0,1-7.309-7.308Zm25.166,104.069a7.317,7.317,0,0,1-7.308,7.309H57.363a7.317,7.317,0,0,1-7.309-7.309V12.42a7.317,7.317,0,0,1,7.309-7.309h55.424V22.969a12.418,12.418,0,0,0,12.419,12.419h17.857v77.406Z" transform="translate(-44.944)" fill="#5850ec"/></g></g><g transform="translate(97.529 85.96)"><path d="M263.664,351.492H262.52a2.555,2.555,0,0,0,0,5.111h1.144a2.555,2.555,0,1,0,0-5.111Z" transform="translate(-259.965 -351.492)" fill="#5850ec"/></g><g transform="translate(64.441 85.96)"><path d="M152.045,351.492H127.224a2.555,2.555,0,0,0,0,5.111h24.821a2.555,2.555,0,0,0,0-5.111Z" transform="translate(-124.669 -351.492)" fill="#5850ec"/></g><g transform="translate(64.442 74.284)"><path d="M186.35,303.747H127.225a2.555,2.555,0,1,0,0,5.111H186.35a2.555,2.555,0,0,0,0-5.111Z" transform="translate(-124.67 -303.747)" fill="#5850ec"/></g><g transform="translate(64.442 62.607)"><path d="M186.35,256H127.225a2.555,2.555,0,1,0,0,5.111H186.35a2.555,2.555,0,0,0,0-5.111Z" transform="translate(-124.67 -256)" fill="#5850ec"/></g><g transform="translate(64.442 50.93)"><path d="M186.35,208.255H127.225a2.555,2.555,0,1,0,0,5.111H186.35a2.555,2.555,0,0,0,0-5.111Z" transform="translate(-124.67 -208.255)" fill="#5850ec"/></g></g></g></svg>
					</a>
				</div>
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
		<input type="text" name="<?php echo $field['slug']; ?>" value="<?php echo $field['value']; ?>" />
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
		$toggle = ! ! $group['toggle'];
		$fields = $group['fields'];
		ob_start();
		?>
		<div class="plausible-analytics-admin-field">
			<div class="plausible-analytics-admin-field-header">
				<label for="">
					<?php echo esc_attr( $group['label'] ); ?>
				</label>
				<?php if ( $toggle ) { ?>
				<label class="plausible-analytics-switch">
					<input checked="checked" class="plausible-analytics-switch-checkbox" name="plausible_analytics_settings[<?php echo $group['slug']; ?>]" value="1" type="checkbox">
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
			<input type="checkbox" name="<?php echo $field['slug']; ?>" /> <?php echo $field['value']; ?>
			<?php echo esc_attr( $field['label'] ); ?>
		</span>
		<?php
		return ob_get_clean();
	}
}
