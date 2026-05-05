<?php
/**
 * Plausible Analytics | Admin Actions.
 *
 * @since      2.5.8
 * @package    WordPress
 * @subpackage Plausible Analytics
 */

namespace Plausible\Analytics\WP\Admin;

class PrivacyPolicy {
	/**
	 * Constructor.
	 */
	public function __construct() {
		$this->init();
	}

	/**
	 * Action & filter hooks.
	 *
	 * @return void
	 */
	private function init() {
		add_action( 'admin_init', [ $this, 'add_suggested_content' ] );
	}

	/**
	 * The content to add to WP's Privacy Policy page.
	 *
	 * @return void
	 *
	 * @codeCoverageIgnore
	 */
	public function add_suggested_content() {
		if ( ! function_exists( 'wp_add_privacy_policy_content' ) ) {
			return;
		}

		$content = '<h2 class="wp-block-heading">' . __( 'Analytics', 'plausible-analytics' ) . '</h2>';
		$content .= '<p>' . '<strong class="privacy-policy-tutorial">' . __( 'Suggested text:', 'plausible-analytics' ) . '</strong></p>';
		$content .= sprintf(
		/* translators: %s: URL to Plausible's data policy page. */
			__( "We use Plausible Analytics to collect usage statistics about our website. Plausible is a privacy-focused analytics provider that does not use cookies or other persistent identifiers.

The data collected includes information such as page URLs, referrer, device type, browser and country. The data is processed by Plausible Analytics on servers located in the European Union.

For more details, see Plausible's data policy: %s", 'plausible-analytics' ),
			'<a href="https://plausible.io/data-policy" target="_blank" rel="noopener noreferrer">https://plausible.io/data-policy</a>'
		);

		wp_add_privacy_policy_content( 'Plausible Analytics', wp_kses_post( wpautop( $content, false ) ) );
	}
}
