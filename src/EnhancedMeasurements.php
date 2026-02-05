<?php

namespace Plausible\Analytics\WP;

/**
 * This class behaves like an enum, while we can't really support yet, since WP hasn't dropped support yet for PHP 8.0 and lower.
 *
 * @codeCoverageIgnore
 */
final class EnhancedMeasurements {
	const FOUR_O_FOUR = '404';

	const FILE_DOWNLOADS = 'file-downloads';

	const OUTBOUND_LINKS = 'outbound-links';

	const CLOAKED_AFFILIATE_LINKS = 'affiliate-links';

	const PAGEVIEW_PROPS = 'pageview-props';

	const ECOMMERCE_REVENUE = 'revenue';

	const FORM_COMPLETIONS = 'form-completions';

	const LOGGED_IN_USER_STATUS = 'user-logged-in';

	const QUERY_PARAMS = 'query-params';

	const SEARCH_QUERIES = 'search';

	const HASH_BASED_ROUTING = 'hash';

	/**
	 * Source of Truth for Enhanced Measurements.
	 */
	private const AVAILABLE_OPTIONS = [
		self::FOUR_O_FOUR,
		self::FILE_DOWNLOADS,
		self::OUTBOUND_LINKS,
		self::CLOAKED_AFFILIATE_LINKS,
		self::PAGEVIEW_PROPS,
		self::ECOMMERCE_REVENUE,
		self::FORM_COMPLETIONS,
		self::LOGGED_IN_USER_STATUS,
		self::QUERY_PARAMS,
		self::SEARCH_QUERIES,
		self::HASH_BASED_ROUTING,
	];

	/**
	 * Check if a certain Enhanced Measurement is enabled.
	 *
	 * @TODO: Refactor $name to enum (introduced in PHP 8.1) when WordPress drops support for PHP 8.0 and lower.
	 *
	 * @param string $name Name of the option to check, valid values are defined in @var self::AVAILABLE_OPTIONS
	 * @param array $enhanced_measurements Allows checking against a different set of options.
	 *
	 * @return bool
	 */
	public static function is_enabled( $name, $enhanced_measurements = [] ) {
		self::is_valid( $name );

		if ( empty( $enhanced_measurements ) ) {
			$enhanced_measurements = Helpers::get_settings()['enhanced_measurements'];
		}

		if ( ! is_array( $enhanced_measurements ) ) {
			return false; // @codeCoverageIgnore
		}

		return apply_filters( 'plausible_analytics_enhanced_measurements_is_enabled', in_array( $name, $enhanced_measurements ) );
	}

	/**
	 * Validate if requested Enhanced Measurement is valid.
	 *
	 * @param $name
	 *
	 * @return void
	 */
	private static function is_valid( $name ) {
		if ( ! in_array( $name, self::AVAILABLE_OPTIONS, true ) ) {
			throw new \InvalidArgumentException(
				sprintf(
					'Invalid enhanced measurement "%s". Allowed values: %s',
					$name,
					implode( ', ', self::AVAILABLE_OPTIONS )
				)
			);
		}
	}
}
