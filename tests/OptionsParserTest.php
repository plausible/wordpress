<?php

namespace Plausible\Analytics\Tests;

use PHPUnit\Framework\TestCase;
use Plausible\Analytics\WP\Admin\Settings\OptionsParser;

class OptionsParserTest extends TestCase {
	public function test_parse_keyed_options() {
		$options = [
			[ 'name' => 'domain_name[english.dev.local]', 'value' => 'english.plausible.io' ],
			[ 'name' => 'api_token[english.dev.local]', 'value' => 'token1' ],
			[ 'name' => 'domain_name[dutch.dev.local]', 'value' => 'dutch.plausible.io' ],
			[ 'name' => 'api_token[dutch.dev.local]', 'value' => 'token2' ],
			[ 'name' => 'excluded_pages[0]', 'value' => '/test' ],
			[ 'name' => 'proxy_enabled', 'value' => 'on' ],
			[ 'name' => 'domain_name[1example.com]', 'value' => 'one.plausible.io' ],
		];

		$settings = [
			'domain_name' => [
				'other.dev.local' => 'other.plausible.io',
			],
			'api_token' => 'old-string-token',
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
		$this->assertEquals( 'dutch.plausible.io', $rebuilt_map['domain_name']['dutch.dev.local'] );
		$this->assertEquals( 'other.plausible.io', $rebuilt_map['domain_name']['other.dev.local'] );
		$this->assertEquals( 'one.plausible.io', $rebuilt_map['domain_name']['1example.com'] );

		// Verify merged api_token (started as string, should become array)
		$this->assertIsArray( $rebuilt_map['api_token'] );
		$this->assertEquals( 'token1', $rebuilt_map['api_token']['english.dev.local'] );
		$this->assertEquals( 'token2', $rebuilt_map['api_token']['dutch.dev.local'] );

		// Verify numeric key stayed as is
		$this->assertEquals( '/test', $rebuilt_map['excluded_pages[0]'] );

		// Verify simple option stayed as is
		$this->assertEquals( 'on', $rebuilt_map['proxy_enabled'] );

		// Verify posted_values (holds the first encountered value for each keyed option)
		$this->assertEquals( 'english.plausible.io', $posted_values['domain_name'] );
		$this->assertEquals( 'token1', $posted_values['api_token'] );
	}

	public function test_no_keyed_options() {
		$options = [
			[ 'name' => 'domain_name', 'value' => 'plausible.io' ],
			[ 'name' => 'excluded_pages[0]', 'value' => '/test' ],
		];
		$settings = [];

		$result = OptionsParser::parse_keyed_options( $options, $settings );

		$this->assertEquals( $options, $result['options'] );
		$this->assertEmpty( $result['posted_values'] );
	}
}
