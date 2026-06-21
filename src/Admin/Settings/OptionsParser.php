<?php

namespace Plausible\Analytics\WP\Admin\Settings;

class OptionsParser {
	/**
	 * Detect elements whose name matches name[key] where key is NOT purely numeric.
	 *
	 * @param array $options
	 * @param array $settings
	 *
	 * @return array
	 */
	public static function parse_keyed_options( array $options, array $settings ) {
		$rebuilt       = [];
		$posted_values = [];
		$has_keyed     = false;

		foreach ( $options as $option ) {
			$name  = $option['name'];
			$value = $option['value'];

			if ( preg_match( '/^([^\[]+)\[([^\]]+)\]$/', $name, $matches ) ) {
				$array_name = $matches[1];
				$key        = $matches[2];

				// Exclude purely-numeric keys.
				if ( ! ctype_digit( $key ) ) {
					$has_keyed = true;

					// Sanitize key: allow only [A-Za-z0-9.-]
					$key = preg_replace( '/[^A-Za-z0-9.\-]/', '', $key );

					if ( ! isset( $posted_values[ $array_name ] ) ) {
						$posted_values[ $array_name ] = $value;
					}

					// Seed from settings or existing rebuilt.
					if ( ! isset( $rebuilt[ $array_name ] ) ) {
						$current_array = $settings[ $array_name ] ?? [];
						if ( ! is_array( $current_array ) ) {
							$current_array = [];
						}
						$rebuilt[ $array_name ] = $current_array;
					}

					$rebuilt[ $array_name ][ $key ] = $value;
					continue;
				}
			}

			$rebuilt[ $name ] = $value;
		}

		if ( ! $has_keyed ) {
			return [
				'options'       => $options,
				'posted_values' => [],
			];
		}

		$rebuilt_options = [];
		foreach ( $rebuilt as $name => $value ) {
			$rebuilt_options[] = [
				'name'  => $name,
				'value' => $value,
			];
		}

		return [
			'options'       => $rebuilt_options,
			'posted_values' => $posted_values,
		];
	}
}
