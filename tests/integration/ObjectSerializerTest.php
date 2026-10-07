<?php
/**
 * @package Plausible Analytics Integration Tests - API Client - ObjectSerializer
 */

namespace Plausible\Analytics\Tests\Integration;

use Plausible\Analytics\Tests\TestCase;
use Plausible\Analytics\WP\Client\ObjectSerializer;

class ObjectSerializerTest extends TestCase {
	/**
	 * @see ObjectSerializer::jsonEncode()
	 * @return void
	 */
	public function testJsonEncode() {
		$this->assertEquals( '{"name":"Woo Complete Purchase"}', ObjectSerializer::jsonEncode( [ 'name' => 'Woo Complete Purchase' ] ) );

		$this->expectException( \InvalidArgumentException::class );

		// Invalid UTF-8 can't be encoded.
		ObjectSerializer::jsonEncode( [ 'name' => "\xB1\x31" ] );
	}

	/**
	 * The generated API client must not call Guzzle's deprecated JSON helpers: since Guzzle 7.15 they report each call
	 * through the global trigger_deprecation(), which another plugin may have defined in a way that fatals on PHP 7.x.
	 * This fails after regenerating the client, until Utils::jsonEncode() is replaced by ObjectSerializer::jsonEncode()
	 * again.
	 *
	 * @return void
	 */
	public function testGeneratedClientDoesNotCallDeprecatedGuzzleJsonHelpers() {
		foreach ( glob( dirname( __DIR__, 2 ) . '/src/Client/lib/Api/*.php' ) as $file ) {
			$this->assertDoesNotMatchRegularExpression(
				'/GuzzleHttp\\\\Utils::json(En|De)code\(/',
				file_get_contents( $file ),
				basename( $file ) . ' calls a deprecated Guzzle JSON helper: use ObjectSerializer::jsonEncode() instead.'
			);
		}
	}
}
