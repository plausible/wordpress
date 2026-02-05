<?php
/**
 * Plausible Analytics | Integrations | Form Submissions.
 * @since      2.2.0
 * @package    WordPress
 * @subpackage Plausible Analytics
 */

namespace Plausible\Analytics\WP\Integrations;

use Plausible\Analytics\WP\Proxy;

class FormSubmit {
	/**
	 * Build class.
	 *
	 * @codeCoverageIgnore
	 */
	public function __construct() {
		$this->init();
	}

	/**
	 * Init
	 *
	 * @return void
	 *
	 * @codeCoverageIgnore
	 */
	private function init() {
		/**
		 * Adds required JS and classes.
		 */
		add_action( 'wp_enqueue_scripts', [ $this, 'add_js' ], 1 );
		/**
		 * Contact Form 7 doesn't respect JS' checkValidity() function, so this is a custom compatibility fix.
		 */
		add_filter( 'wpcf7_validate', [ $this, 'maybe_track_submission' ], 10, 2 );
		/**
		 * Gravity Forms contains its own form submission handler, so this is a custom compatibility fix.
		 */
		add_action( 'gform_after_submission', [ $this, 'track_gravity_forms_submission' ], 10 );
	}

	/**
	 * Enqueues the required JavaScript for form submissions integration.
	 * @return void
	 *
	 * @codeCoverageIgnore because there's nothing to test here.
	 */
	public function add_js() {
		wp_register_script(
			'plausible-form-submit-integration',
			PLAUSIBLE_ANALYTICS_PLUGIN_URL . 'assets/dist/js/plausible-form-submit-integration.js',
			[ 'plausible-analytics' ],
			filemtime( PLAUSIBLE_ANALYTICS_PLUGIN_DIR . 'assets/dist/js/plausible-form-submit-integration.js' )
		);

		wp_localize_script(
			'plausible-form-submit-integration',
			'plausible_analytics_i18n',
			[ 'form_completions' => __( 'WP Form Completions', 'plausible-analytics' ), ]
		);

		wp_enqueue_script( 'plausible-form-submit-integration' );
	}

	/**
	 * Tracks the form submission if CF7 says it's valid.
	 *
	 * @filter             wpcf7_validate
	 *
	 * @param \WPCF7_Validation $result Form submission result object containing validation results.
	 * @param array $tags Array of tags associated with the form fields.
	 *
	 * @return \WPCF7_Validation
	 *
	 * @codeCoverageIgnore because we can't test XHR requests here.
	 */
	public function maybe_track_submission( $result, $tags ) {
		$invalid_fields = $result->get_invalid_fields();

		if ( empty( $invalid_fields ) ) {
			$post = get_post( $_POST['_wpcf7_container_post'] );
			$uri  = '/' . $post->post_name . '/';

			$this->track_submission( $uri );
		}

		return $result;
	}

	/**
	 * Track submission using the Proxy.
	 *
	 * @param $uri
	 *
	 * @return void
	 *
	 * @codeCoverageIgnore because we can't test XHR requests here.
	 */
	private function track_submission( $uri ) {
		$proxy = new Proxy( false );

		$proxy->do_request(
			__( 'WP Form Completions', 'plausible-analytics' ),
			null,
			null,
			[ 'path' => $uri ]
		);
	}

	/**
	 * Compatibility fix for Gravity Forms.
	 *
	 * @action             gform_after_submission
	 *
	 * @param $form
	 * @param $entry
	 *
	 * @return void
	 *
	 * @codeCoverageIgnore because we can't test XHR requests here.
	 */
	public function track_gravity_forms_submission( $form ) {
		$uri = str_replace( home_url(), '', $form['source_url'] ) ?? '';

		if ( empty( $uri ) ) {
			return;
		}

		$this->track_submission( $uri );
	}
}
