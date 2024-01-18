<?php /** @noinspection HtmlUnknownTarget */

/**
 * Plausible Analytics | Settings API.
 * @since      1.3.0
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
	 * @since  1.3.0
	 * @access public
	 * @var array
	 */
	public $fields = [];

	/**
	 * Slide IDs and Titles
	 * @since v2.0.0
	 * @var string[] $slides
	 */
	private $slides = [];

	/**
	 * Slide IDs and Descriptions
	 * @since v2.0.0
	 * @var array $slides_description
	 */
	private $slides_description = [];

	/**
	 * Render Fields.
	 * @since  1.3.0
	 * @access public
	 * @return void
	 */
	public function settings_page() {
		wp_nonce_field( 'plausible_analytics_toggle_option' );
		$followed_wizard = get_option( 'plausible_analytics_wizard_done' );

		$this->slides = [
			'welcome'                    => __( 'Welcome to Plausible Analytics v2!', 'plausible-analytics' ),
			'domain_name'                => __( 'Confirm domain', 'plausible-analytics' ),
			'api_token'                  => __( 'Create API token', 'plausible-analytics' ),
			'enable_analytics_dashboard' => __( 'Stats in WP', 'plausible-analytics' ),
			'enhanced_measurements'      => __( 'Enhanced measurements', 'plausible-analytics' ),
			'proxy_enabled'              => __( 'Enable proxy', 'plausible-analytics' ),
			'success'                    => __( 'Success!', 'plausible-analytics' ),
		];

		$this->slides_description = [
			'welcome'                    => sprintf(
				__(
					'<p><a href="%s" target="_blank">Plausible Analytics</a> is an easy to use, open source, lightweight (< 1 KB) and privacy-friendly alternative to Google Analytics. We\'re super excited to have you on board!</p><p>To use our plugin, you need to <a href="%s" target="_blank">register an account</a>. To explore the product, we offer you a free 30-day trial. No credit card is required to sign up for the trial.</p><p>Have an account? Please do follow the following steps to get the most out of your Plausible experience.</p>',
					'plausible-analytics'
				),
				'https://plausible.io/?utm_source=WordPress&utm_medium=Referral&utm_campaign=WordPress+plugin',
				'https://plausible.io/register?utm_source=WordPress&utm_medium=Referral&utm_campaign=WordPress+plugin'
			),
			'domain_name'                => __( 'Confirm your domain name as you\'ve added it to your Plausible account.', 'plausible-analytics' ),
			'api_token'                  => __(
				'Create the API token that we\'ll use to automate part of the remaining setup process',
				'plausible-analytics'
			),
			'enable_analytics_dashboard' => __(
				'Would you like to view your site\'s stats right here in your WordPress dashboard?',
				'plausible-analytics'
			),
			'enhanced_measurements'      => __( 'Enable enhanced measurements', 'plausible-analytics' ),
			'proxy_enabled'              => __(
				'Run our scripts as a 1st party connection from your domain name to count visitors who use adblockers',
				'plausible-analytics'
			),
			'success'                    => sprintf(
				__(
					'<p>Congrats! Your traffic is now being counted without compromising the user experience and privacy of your visitors. You can now check out your intuitive, fast-loading and privacy-friendly dashboard.</p><p>Note that visits from logged in users aren\'t tracked. If you want to track visits for certain user roles, then please specify them here.</p><p>Need help? <a href="%s" target="_blank">Our documentation</a> is the best place to find most answers right away.</p><p>Still haven\'t found the answer you\'re looking for? We\'re here to help. Please <a href="%s" target="_blank">contact our support</a>.</p>',
					'plausible-analytics'
				),
				'https://plausible.io/docs?utm_source=WordPress&utm_medium=Referral&utm_campaign=WordPress+plugin',
				'https://plausible.io/contact?utm_source=WordPress&utm_medium=Referral&utm_campaign=WordPress+plugin'
			),
		];

		if ( ! $followed_wizard ) {
			return $this->show_wizard();
		}

		$current_tab = ! empty( $_GET[ 'tab' ] ) ? $_GET[ 'tab' ] : 'general';
		?>
		<div class="h-full">
			<!-- body -->
			<div class="flex flex-col h-full">
				<!-- logo -->
				<nav class="relative z-20 py-8">
					<div class="container">
						<nav class="relative flex items-center justify-between sm:h-10 md:justify-center">
							<div class="flex items-center flex-1 md:absolute md:inset-y-0 md:left-0">
								<img class="h-8 w-auto sm:h-10 -mt-2 dark:inline" alt="Plausible Logo"
									 src="<?php echo PLAUSIBLE_ANALYTICS_PLUGIN_URL . '/assets/dist/images/icon.png'; ?>"/>
							</div>
						</nav>
					</div>
				</nav>
				<!-- notices -->
				<div
					class="z-50 fixed inset-0 top-5 flex items-end justify-center px-6 py-8 pointer-events-none sm:p-6 sm:items-start sm:justify-end">
					<div id="plausible-analytics-notice"
						 class="max-w-sm w-full bg-white dark:bg-gray-800 shadow-lg rounded-lg pointer-events-auto transition-opacity ease-in-out duration-200 opacity-0">
						<div class="rounded-lg ring-1 ring-black ring-opacity-5 overflow-hidden">
							<div class="p-4">
								<div class="flex items-start">
									<div id="icon-success" class="flex-shrink-0">
										<svg class="h-8 w-6 text-green-400" xmlns="http://www.w3.org/2000/svg" fill="none"
											 viewBox="0 0 24 24"
											 stroke="currentColor">
											<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
												  d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"></path>
										</svg>
									</div>
									<div id="icon-error" class="flex-shrink-0 hidden">
										<svg class="h-8 w-6 text-red-400" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24"
											 stroke-width="2"
											 stroke="currentColor">
											<path stroke-linecap="round" stroke-linejoin="round"
												  d="m9.75 9.75 4.5 4.5m0-4.5-4.5 4.5M21 12a9 9 0 1 1-18 0 9 9 0 0 1 18 0Z"/>
										</svg>
									</div>
									<div class="ml-3 w-0 flex-1 pt-0.5">
										<!-- message -->
										<p id="plausible-analytics-notice-text" class="mt-1 text-sm leading-5 text-gray-500 dark:text-gray-200"></p>
									</div>
								</div>
							</div>
						</div>
					</div>
				</div>
				<!-- /notices -->
				<div class="flex flex-col gap-y-2"></div>
				<!-- navigation -->
				<main class="flex-1">
					<div class="container pt-6">
						<div class="pb-5 border-b border-gray-200 dark:border-gray-500">
							<h1 class="text-2xl font-bold leading-7 text-gray-900 dark:text-gray-100 sm:text-3xl sm:leading-9 sm:truncate">
								<?php esc_html_e( 'Settings', 'plausible-analytics' ); ?>
							</h1>
						</div>
						<div class="lg:grid lg:grid-cols-12 lg:gap-x-5 lg:mt-4">
							<div class="py-4 g:py-0 lg:col-span-3">
								<?php $this->render_navigation(); ?>
								<?php echo Helpers::render_quick_actions(); ?>
							</div>
							<div class="space-y-6 lg:col-span-9 lg:mt-4">
								<?php foreach ( $this->fields[ $current_tab ] as $tab => $field ): ?>
									<div class="plausible-analytics-section shadow sm:rounded-md sm:overflow-hidden">
										<?php
										$type = $field[ 'type' ] ?? '';

										if ( $type ) {
											echo call_user_func( [ $this, "render_{$type}_field" ], $field );
										}
										?>
									</div>
								<?php endforeach; ?>
							</div>
						</div>
					</div>
				</main>
				<!-- /navigation -->
			</div>
			<!-- /body -->
		</div>
		<?php
	}

	/**
	 * Renders the configuration wizard on the Settings page.
	 * @return void
	 */
	private function show_wizard() {
		?>
		<div id="plausible-analytics-wizard" class="h-full min-h-[90vh]">
			<!-- body -->
			<div class="flex flex-col h-full">
				<!-- logo -->
				<nav class="relative z-20 py-8">
					<div class="container">
						<nav class="relative flex items-center justify-between sm:h-10 md:justify-center">
							<div class="flex items-center flex-1 md:absolute md:inset-y-0 md:left-0">
								<img class="h-8 w-auto sm:h-10 -mt-2 dark:inline" alt="Plausible Logo"
									 src="<?php echo PLAUSIBLE_ANALYTICS_PLUGIN_URL . '/assets/dist/images/icon.png'; ?>"/>
							</div>
						</nav>
					</div>
				</nav>
				<div class="flex flex-col gap-y-2"></div>
				<!-- navigation -->
				<main class="flex-1">
					<div class="container pt-6">
						<div class="pb-5 border-b border-gray-200 dark:border-gray-500">
							<h1 class="text-2xl font-bold leading-7 text-gray-900 dark:text-gray-100 sm:text-3xl sm:leading-9 sm:truncate">
								<?php esc_html_e( 'Configuration Wizard', 'plausible-analytics' ); ?>
							</h1>
						</div>
						<div class="lg:grid lg:grid-cols-12 lg:gap-x-5 lg:mt-4">
							<div class="py-4 g:py-0 lg:col-span-3">
								<?php foreach ( $this->slides as $id => $title ): ?>
									<div class="lg:block">
										<?php
										printf(
											'<a id="step-%1$s" href="#%1$s" class="plausible-analytics-wizard-step no-underline flex items-center px-3 py-2 text-sm leading-5 text-gray-600 dark:text-gray-400 rounded-md hover:text-gray-900 dark:hover:text-gray-100 hover:bg-gray-50 dark:hover:bg-gray-800 outline-none focus:outline-none focus:text-gray-900 focus:bg-gray-50 dark:focus:text-gray-100 dark:focus:bg-gray-800 ease-in-out duration-150">%2$s</a>',
											esc_attr( $id ),
											esc_html( $title )
										);
										?>
									</div>
								<?php endforeach; ?>
							</div>
							<div class="space-y-6 lg:col-span-9 lg:mt-4 relative">
								<div class="plausible-analytics-section">
									<?php
									$slide_ids = array_keys( $this->slides );
									$i         = 0;
									?>
									<?php foreach ( $this->slides as $id => $title ): ?>
										<div id="<?php esc_attr_e( $id, 'plausible-analytics' ); ?>" class="plausible-analytics-group shadow sm:rounded-md sm:overflow-hidden bg-white dark:bg-gray-800 py-6 px-4
										 space-y-6 sm:p-6 invisible target:opacity-100 target:visible transition-opacity absolute w-5/6">
											<header class="relative">
												<label class="text-lg leading-6 font-medium text-gray-900 dark:text-gray-100"
													   for=""><?php echo $title; ?></label>
											</header>
											<div class="mt-1 text-sm leading-5 !text-gray-500 !dark:text-gray-200">
												<?php echo wp_kses_post( $this->slides_description[ $id ] ); ?>
											</div>
											<div class="plausible-analytics-wizard-step-section">
												<?php
												$field = $this->get_wizard_option_properties( $id );

												if ( ! empty( $field ) ) {
													$hide_header = $field[ 'type' ] === 'group';

													echo call_user_func( [ $this, "render_{$field['type']}_field" ], $field, $hide_header );
												}
												?>
												<?php ++ $i; ?>
												<div class="mt-6">
													<?php if ( array_key_exists( $i, $slide_ids ) ) : ?>
														<a id="plausible-analytics-wizard-next-step-<?php echo $id; ?>"
														   href="#<?php esc_attr_e( $slide_ids[ $i ], 'plausible-analytics' ); ?>"
														   class="plausible-analytics-wizard-next-step no-underline gap-x-2 inline-flex relative inset-0 rounded-md bg-indigo-600 px-3.5 py-2.5 text-sm font-semibold text-white shadow-sm hover:bg-indigo-700 hover:text-white focus-visible:outline focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-indigo-600">
															<svg id="plausible-analytics-wizard-next-step-spinner-<?php echo $id; ?>"
																 class="hidden animate-spin m-auto -ml-1 mr-1 h-5 w-5 text-white transition-translate"
																 xmlns="http://www.w3.org/2000/svg"
																 fill="none" viewBox="0 0 24 24">
																<circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor"
																		stroke-width="4"></circle>
																<path class="opacity-75" fill="currentColor"
																	  d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
															</svg>
															<?php esc_html_e( 'Next', 'plausible-analytics' ); ?>
														</a>
													<?php else: ?>
														<a id="plausible-analytics-wizard-quit"
														   data-nonce="<?php echo wp_create_nonce( 'plausible_analytics_quit_wizard' ); ?>" href="#"
														   class="no-underline gap-x-2 rounded-md bg-indigo-600 px-3.5 py-2.5 text-sm font-semibold text-white shadow-sm hover:bg-indigo-700 hover:text-white focus-visible:outline focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-indigo-600">
															<?php esc_html_e( 'Exit Wizard', 'plausible-analytics' ); ?>
														</a>
													<?php endif; ?>
												</div>
											</div>
										</div>
									<?php endforeach; ?>
								</div>
							</div>
						</div>
					</div>
				</main>
			</div>
			<!-- /body -->
		</div>
		<?php
	}

	/**
	 * Get settings field by slug.
	 *
	 * @param $slug
	 *
	 * @return array|mixed
	 */
	private function get_wizard_option_properties( $slug ) {
		foreach ( $this->fields[ 'general' ] as $key => $group ) {
			if ( $group[ 'slug' ] === $slug ) {
				return $group;
			}

			foreach ( $group[ 'fields' ] as $key => $field ) {
				if ( $field[ 'slug' ] === $slug ) {
					return $field;
				}
			}
		}

		return [];
	}

	/**
	 * Render Header Navigation.
	 * @since  1.3.0
	 * @access public
	 * @return void
	 */
	public function render_navigation() {
		$screen = get_current_screen();

		// Bailout, if screen id doesn't match.
		if ( 'settings_page_plausible_analytics' !== $screen->id ) {
			return;
		}

		$current_tab = ! empty( $_GET[ 'tab' ] ) ? $_GET[ 'tab' ] : '';
		$tabs        = apply_filters(
			'plausible_analytics_settings_navigation_tabs',
			[
				'general'     => [
					'name'  => esc_html__( 'General', 'plausible-analytics' ),
					'url'   => admin_url( 'options-general.php?page=plausible_analytics' ),
					'class' => '' === $current_tab ? 'active' : '',
				],
				'self-hosted' => [
					'name'  => esc_html__( 'Self-Hosted', 'plausible-analytics' ),
					'url'   => admin_url( 'options-general.php?page=plausible_analytics&tab=self-hosted' ),
					'class' => 'self-hosted' === $current_tab ? 'active' : '',
				],
			]
		);
		?>
		<div class="hidden lg:block">
			<?php
			foreach ( $tabs as $tab ) {
				printf(
					'<a href="%1$s" class="no-underline flex items-center px-3 py-2 text-sm leading-5 font-medium text-gray-600 dark:text-gray-400 rounded-md hover:text-gray-900 dark:hover:text-gray-100 hover:bg-gray-50 dark:hover:bg-gray-800 outline-none focus:outline-none focus:text-gray-900 focus:bg-gray-50 dark:focus:text-gray-100 dark:focus:bg-gray-800 transition ease-in-out duration-150 %2$s">%3$s</a>',
					esc_url( $tab[ 'url' ] ),
					esc_attr( $tab[ 'class' ] ),
					esc_html( $tab[ 'name' ] )
				);
			}
			?>
		</div>
		<?php
	}

	/**
	 * Render Group Field.
	 * @since  1.3.0
	 * @access public
	 * @return string
	 */
	public function render_group_field( array $group, $hide_header = false ) {
		$toggle = $group[ 'toggle' ] ?? [];
		$fields = $group[ 'fields' ];
		ob_start();
		?>
		<div class="<?php echo $hide_header ? '' : 'plausible-analytics-group py-6 px-4 space-y-6 sm:p-6'; ?> bg-white dark:bg-gray-800">
			<?php if ( ! $hide_header ) : ?>
				<header class="relative">
					<label class="text-lg leading-6 font-medium text-gray-900 dark:text-gray-100" for=""><?php echo $group[ 'label' ]; ?></label>
					<div class="mt-1 text-sm leading-5 !text-gray-500 !dark:text-gray-200">
						<?php echo wp_kses_post( $group[ 'desc' ] ); ?>
					</div>
					<?php if ( ! empty( $toggle ) && is_array( $toggle ) ) : ?>
						<a target="_blank" class="plausible-analytics-link" href="<?php echo $toggle[ 'anchor' ]; ?>">
							<?php echo $toggle[ 'label' ]; ?>
						</a>
					<?php endif; ?>
				</header>
			<?php endif; ?>
			<?php if ( ! empty( $fields ) ): ?>
				<?php $is_list = count( $fields ) > 1; ?>
				<?php if ( $is_list ) {
					foreach ( $fields as $field ) {
						if ( $field[ 'type' ] !== 'checkbox' ) {
							$is_list = false;

							break;
						}
					}
				}
				foreach ( $fields as $field ): ?>
					<?php echo call_user_func( [ $this, "render_{$field['type']}_field" ], $field, $is_list ); ?>
				<?php endforeach; ?>
			<?php endif; ?>
		</div>
		<?php
		return ob_get_clean();
	}

	/**
	 * Render Text Field.
	 * @since  1.3.0
	 * @access public
	 * @return string
	 */
	public function render_text_field( array $field ) {
		ob_start();
		$value       = ! empty( $field[ 'value' ] ) ? $field[ 'value' ] : '';
		$placeholder = ! empty( $field[ 'placeholder' ] ) ? $field[ 'placeholder' ] : '';
		$disabled    = ! empty( $field[ 'disabled' ] ) ? 'disabled' : '';
		?>
		<div class="mt-4">
			<label class="block text-sm font-medium leading-5 !text-gray-700 !dark:text-gray-300"
				   for="<?php echo $field[ 'slug' ]; ?>"><?php echo esc_attr( $field[ 'label' ] ); ?></label>
			<div class="mt-1">
				<input
					class="block w-full !border-gray-300 !dark:border-gray-700 !rounded-md focus:ring-indigo-500 focus:border-indigo-500 sm:text-sm dark:bg-gray-900 dark:text-gray-300 py-2 px-3"
					id="<?php echo $field[ 'slug' ]; ?>" placeholder="<?php echo $placeholder; ?>" type="text"
					name="<?php echo $field[ 'slug' ]; ?>"
					value="<?php echo $value; ?>" <?php echo $disabled; ?> />
			</div>
		</div>
		<?php
		return ob_get_clean();
	}

	/**
	 * Renders a button.
	 *
	 * @param array $field
	 *
	 * @return false|string
	 */
	public function render_button_field( array $field ) {
		ob_start();
		$disabled = isset( $field[ 'disabled' ] ) && $field[ 'disabled' ] === true;
		?>
		<div>
			<button
				class="plausible-analytics-button border-0 hover:cursor-pointer inline-flex items-center justify-center !gap-x-2 rounded-md bg-indigo-600 px-3.5 py-2.5 text-sm font-semibold text-white shadow-sm hover:bg-indigo-700 focus-visible:outline focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-indigo-600 disabled:bg-gray-400 dark:disabled:bg-gray-800 ease-in-out transition-all"
				id="<?php esc_attr_e( $field[ 'slug' ], 'plausible-analytics' ); ?>"
				type="submit" <?php echo $disabled ? 'disabled' : ''; ?>>
				<svg class="animate-spin -ml-1 mr-3 h-5 w-5 text-white hidden" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
					<circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
					<path class="opacity-75" fill="currentColor"
						  d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
				</svg>
				<?php esc_attr_e( $field[ 'label' ], 'plausible-analytics' ); ?>
			</button>
		</div>
		<?php
		return ob_get_clean();
	}

	/**
	 * Render Checkbox Field.
	 * @since  1.3.0
	 * @access public
	 * @return string
	 */
	public function render_checkbox_field( array $field, $is_list = false ) {
		ob_start();
		$value    = ! empty( $field[ 'value' ] ) ? $field[ 'value' ] : 'on';
		$settings = Helpers::get_settings();
		$slug     = ! empty( $settings[ $field[ 'slug' ] ] ) ? $settings[ $field[ 'slug' ] ] : '';
		$id       = $field[ 'slug' ] . '_' . str_replace( '-', '_', sanitize_title( $field[ 'label' ] ) );
		$checked  =
			! empty( $field[ 'checked' ] ) ? 'checked="checked"' :
				( is_array( $slug ) ? checked( $value, in_array( $value, $slug, false ) ? $value : false, false ) : checked( $value, $slug, false ) );
		?>
		<div class="flex items-center mt-4 space-x-3">
			<button
				class="plausible-analytics-toggle <?php echo $checked ? 'bg-indigo-600' :
					'bg-gray-200'; ?> dark:bg-gray-700 relative inline-flex flex-shrink-0 h-6 w-11 border-2 border-transparent rounded-full cursor-pointer transition-colors ease-in-out duration-200 focus:outline-none focus:ring"
				id="<?php /** @noinspection PhpUnnecessaryLocalVariableInspection */
				echo $id; ?>" type="checkbox" data-list="<?php echo $is_list ? '1' : ''; ?>"
				name="<?php echo esc_attr( $field[ 'slug' ] ); ?>"
				value="<?php echo esc_html( $value ); ?>">
				<span class="plausible-analytics-toggle <?php echo $checked ? 'translate-x-5' :
					'translate-x-0'; ?> inline-block h-5 w-5 rounded-full bg-white dark:bg-gray-800 shadow transform transition-translate ease-in-out duration-200"></span>
			</button>
			<span class="ml-2 dark:text-gray-100 text-lg"><?php echo $field[ 'label' ]; ?></span>
			<?php if ( isset( $field[ 'docs' ] ) ): ?>
				<a class="leading-none" href="<?php echo esc_url( $field[ 'docs' ] ); ?>" rel="noreferrer" target="_blank">
					<svg xmlns="http://www.w3.org/2000/svg" class="text-gray-400 w-6 h-6 leading-none" stroke="currentColor"
						 aria-hidden="true"
						 fill="none" viewBox="0 0 24 24" stroke-width="1.5">
						<path stroke-linecap="round" stroke-linejoin="round"
							  d="M11.25 11.25l.041-.02a.75.75 0 011.063.852l-.708 2.836a.75.75 0 001.063.853l.041-.021M21 12a9 9 0 11-18 0 9 9 0 0118 0zm-9-3.75h.008v.008H12V8.25z"></path>
					</svg>
				</a>
			<?php endif; ?>
		</div>
		<?php
		return ob_get_clean();
	}

	/**
	 * Render textarea field.
	 * @since  1.2.5
	 * @access public
	 *
	 * @param array $field
	 *
	 * @return string|false
	 */
	public function render_textarea_field( array $field ) {
		ob_start();
		$value       = ! empty( $field[ 'value' ] ) ? $field[ 'value' ] : '';
		$placeholder = ! empty( $field[ 'placeholder' ] ) ? $field[ 'placeholder' ] : '';
		?>
		<div class="mt-4">
			<label class="block text-sm font-medium text-gray-700 dark:text-gray-300" for="<?php echo esc_attr( $field[ 'slug' ] ); ?>">
				<?php echo esc_attr( $field[ 'label' ] ); ?>
			</label>
			<div class="relative mt-1">
			<textarea
				class="block w-full max-w-xl border-gray-300 dark:border-gray-700 resize-none shadow-sm focus:ring-indigo-500 focus:border-indigo-500 sm:text-sm rounded-md dark:bg-gray-900 dark:text-gray-300"
				rows="5" id="<?php echo esc_attr( $field[ 'slug' ] ); ?>"
				placeholder="<?php echo esc_attr( $placeholder ); ?>"
				name="<?php echo esc_attr( $field[ 'slug' ] ); ?>"><?php echo $value; ?></textarea>
			</div>
		</div>
		<?php
		return ob_get_clean();
	}

	/**
	 * Render just the label, and allow insertion of anything using the hook beside it.
	 * @since 1.3.0
	 *
	 * @param array $field
	 *
	 * @return string|false
	 */
	public function render_hook_field( array $field ) {
		ob_start();
		?>
		<div class="">
			<div class="rounded-md p-4 mt-4 relative bg-yellow-50 dark:bg-yellow-100 rounded-t-md rounded-b-none">
				<div class="flex">
					<div class="flex-shrink-0">
						<svg class="h-12 w-5 text-yellow-400" viewBox="0 0 20 20" fill="currentColor" aria-hidden="true">
							<path fill-rule="evenodd"
								  d="M8.485 2.495c.673-1.167 2.357-1.167 3.03 0l6.28 10.875c.673 1.167-.17 2.625-1.516 2.625H3.72c-1.347 0-2.189-1.458-1.515-2.625L8.485 2.495zM10 5a.75.75 0 01.75.75v3.5a.75.75 0 01-1.5 0v-3.5A.75.75 0 0110 5zm0 9a1 1 0 100-2 1 1 0 000 2z"
								  clip-rule="evenodd"></path>
						</svg>
					</div>
					<div class="w-full ml-3 <?php echo esc_attr( str_replace( '_', '-', $field[ 'slug' ] ) ); ?>">
						<div class="text-sm text-yellow-700 dark:text-yellow-800">
							<p><?php do_action( 'plausible_analytics_settings_' . $field[ 'slug' ], $field[ 'slug' ] ); ?></p>
						</div>
					</div>
				</div>
			</div>
		</div>
		<?php

		return trim( ob_get_clean() );
	}
}
