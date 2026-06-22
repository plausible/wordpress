<?php

namespace Plausible\Analytics\Tests;

use PHPUnit\Framework\TestCase;
use Plausible\Analytics\WP\Admin\Settings\OptionsParser;

class OptionsParserTest extends TestCase {
	public function test_no_keyed_options() {
		$options  = [
			[ 'name' => 'domain_name', 'value' => 'plausible.io' ],
		];
		$settings = [];

		$result = OptionsParser::parse_keyed_options( $options, $settings );

		$this->assertEquals( $options, $result['options'] );
		$this->assertEmpty( $result['posted_values'] );
	}

	public function test_parse_keyed_options() {
		$options = [
			[ 'name' => 'domain_name[english.dev.local]', 'value' => 'english.plausible.io' ],
			[ 'name' => 'api_token[english.dev.local]', 'value' => 'token1' ],
			[ 'name' => 'domain_name[default]', 'value' => 'main.plausible.io' ],
			[ 'name' => 'api_token[default]', 'value' => 'token-main' ],
			[ 'name' => 'excluded_pages[0]', 'value' => '/test' ],
			[ 'name' => 'proxy_enabled', 'value' => 'on' ],
		];

		$settings = [
			'domain_name' => [
				'other.dev.local' => 'other.plausible.io',
			],
			'api_token'   => [
				'other.dev.local' => 'token-other',
			],
		];

		$result = OptionsParser::parse_keyed_options( $options, $settings );

		$rebuilt       = $result['options'];
		$posted_values = $result['posted_values'];

		$rebuilt_map = [];
		foreach ( $rebuilt as $option ) {
			$rebuilt_map[ $option['name'] ] = $option['value'];
		}

		// Verify merged domain_name
		$this->assertIsArray( $rebuilt_map['domain_name'] );
		$this->assertEquals( 'english.plausible.io', $rebuilt_map['domain_name']['english.dev.local'] );
		$this->assertEquals( 'main.plausible.io', $rebuilt_map['domain_name']['default'] );
		$this->assertEquals( 'other.plausible.io', $rebuilt_map['domain_name']['other.dev.local'] );

		// Verify merged api_token
		$this->assertIsArray( $rebuilt_map['api_token'] );
		$this->assertEquals( 'token1', $rebuilt_map['api_token']['english.dev.local'] );
		$this->assertEquals( 'token-main', $rebuilt_map['api_token']['default'] );
		$this->assertEquals( 'token-other', $rebuilt_map['api_token']['other.dev.local'] );

		// Verify numeric key (should now be parsed and merged)
		$this->assertIsArray( $rebuilt_map['excluded_pages'] );
		$this->assertEquals( '/test', $rebuilt_map['excluded_pages'][0] );

		// Verify simple option stayed as is
		$this->assertEquals( 'on', $rebuilt_map['proxy_enabled'] );

		// Verify posted_values (holds the first encountered value for each keyed option)
		$this->assertEquals( 'english.plausible.io', $posted_values['domain_name'] );
		$this->assertEquals( 'token1', $posted_values['api_token'] );
	}
}
