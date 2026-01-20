<?php
/**
 * Plausible Analytics | Integrations | Search
 */

namespace Plausible\Analytics\WP\Integrations;

use Plausible\Analytics\WP\EnhancedMeasurements;

class Search {
	/**
	 * Build class.
	 *
	 * @codeCoverageIgnore
	 */
	public function __construct() {
		$this->init();
	}

	/**
	 * Filter/action hooks.
	 *
	 * @return void
	 *
	 * @codeCoverageIgnore
	 */
	private function init() {
		add_filter( 'get_search_form', [ $this, 'maybe_add_hidden_input_to_search_form' ] );
		add_filter( 'render_block', [ $this, 'maybe_add_hidden_input_to_search_block' ], 10, 2 );
	}

	/**
	 * Adds a hidden input field to the search form for enhanced measurement of search referrer.
	 *
	 * This method checks if enhanced measurement is enabled for search, and if so, it appends
	 * a hidden input field containing a reference to the search's referrer URL.
	 *
	 * @param string $form The HTML markup of the search form.
	 *
	 * @return string The modified HTML markup of the search form with the hidden input added,
	 *                or the original form if enhanced measurement is not enabled.
	 *
	 * @codeCoverageIgnore because we wouldn't be testing anything here. Whether this works depends on the filter, and that'd only break if WordPress changes the name of it.
	 */
	public function maybe_add_hidden_input_to_search_form( $form ) {
		if ( ! EnhancedMeasurements::is_enabled( EnhancedMeasurements::SEARCH_QUERIES ) ) {
			return $form;
		}

		$referrer     = $this->get_referrer();
		$hidden_input = '<input type="hidden" name="search_source" value="' . $referrer . '" />';

		return str_replace( '</form>', $hidden_input . '</form>', $form );
	}

	/**
	 * Retrieves the current page URL to be used as a referrer.
	 *
	 * This method constructs the referrer by obtaining the current page URL and ensures
	 * it is sanitized. If the referrer cannot be determined, an empty string is returned.
	 *
	 * @return string The sanitized referrer URL or an empty string if unavailable.
	 *
	 * @codeCoverageIgnore because it's parent methods aren't tested either.
	 */
	private function get_referrer() {
		$referrer = esc_url( home_url( add_query_arg( null, null ) ) );

		if ( ! $referrer ) {
			$referrer = '';
		}

		return esc_attr( $referrer );
	}

	/**
	 * Adds a hidden input field to the content of a search block for enhanced measurement of search referrer.
	 *
	 * This method checks if the given block is a WordPress core search block. If so, it appends
	 * a hidden input field containing a reference to the current page's URL as the search's referrer.
	 * The hidden input is inserted before the button element if present or at the end of the block content otherwise.
	 *
	 * @param string $block_content The current content of the block.
	 * @param array $block The block attributes and settings.
	 *
	 * @return string The modified content of the block with the hidden input added if it is a core search block,
	 *                or the original block content if the block is not a search block.
	 *
	 * @codeCoverageIgnore because we wouldn't be testing anything here. Whether this works depends on the filter, and that'd only break if WordPress changes the name of it.
	 */
	public function maybe_add_hidden_input_to_search_block( $block_content, $block ) {
		if ( $block['blockName'] === 'core/search' ) {
			$referrer     = $this->get_referrer();
			$hidden_input = '<input type="hidden" name="search_source" value="' . $referrer . '"/>';

			if ( str_contains( $block_content, '<button' ) ) {
				$block_content = str_replace( '<button', $hidden_input . '<button', $block_content );
			} else {
				$block_content .= $hidden_input;
			}
		}

		return $block_content;
	}
}
