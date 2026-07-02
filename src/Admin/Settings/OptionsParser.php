<?php

namespace Plausible\Analytics\WP\Admin\Settings;

class OptionsParser {
	/**
	 * Detect array elements whose name matches name[key] where the key is NOT purely numeric.
	 *
	 * @param array $options
	 * @param array $settings
	 *
	 * @return array
	 */
	public static function parse_keyed_options( array $options, array $settings ) {
		$rebuilt       = [];
		$posted_values = [];
		$posted_keys   = [];
		$has_keyed     = false;

		foreach ( $options as $option ) {
			$name  = $option['name'];
			$value = $option['value'];

			if ( preg_match( '/^([^\[]+)\[([^\]]+)\]$/', $name, $matches ) ) {
				$array_name = $matches[1];
				$key        = $matches[2];

				// Allow all keys, but distinguish them.
				$has_keyed = true;

				// Sanitize key: allow only [A-Za-z0-9.-]
				$key = preg_replace( '/[^A-Za-z0-9.\-]/', '', $key );

				if ( ! isset( $posted_values[ $array_name ] ) ) {
					$posted_values[ $array_name ] = $value;
					$posted_keys[ $array_name ]   = $key;
				}

				$is_numeric_key = ctype_digit( $key );

				// Merge associative keys but rebuild numeric lists from the submitted payload.
				if ( ! isset( $rebuilt[ $array_name ] ) ) {
					$current_array = $settings[ $array_name ] ?? [];
					if ( ! is_array( $current_array ) ) {
						$current_array = [];
					}
					$rebuilt[ $array_name ] = $is_numeric_key ? [] : $current_array;
				}

				if ( $is_numeric_key ) {
					$rebuilt[ $array_name ][] = $value;
				} else {
					$rebuilt[ $array_name ][ $key ] = $value;
				}

				continue;
			}

			$rebuilt[ $name ] = $value;
		}

		if ( ! $has_keyed ) {
			return [
				'options'       => $options,
				'posted_values' => [],
				'posted_keys'   => [],
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
			'posted_keys'   => $posted_keys,
		];
	}
}
