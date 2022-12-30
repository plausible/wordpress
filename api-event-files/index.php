<?php

$url = 'https://plausible.io/api/event';

$headers = [];

$allowed_headers =

	[
		'Accept',
		'Accept-Language',
		'Connection',
		'Content-Length',
		'Content-Type',
		'Cookie',
		'Origin',
		'Referer',
		'Sec-Ch-Ua',
		'Sec-Ch-Ua-Mobile',
		'Sec-Ch-Ua-Platform',
		'Sec-Fetch-Dest',
		'Sec-Fetch-Mode',
		'Sec-Fetch-Site',
		'User-Agent',
		'X-Forwarded-Host',
		'X-Forwarded-Port',
		'X-Forwarded-Proto',
		'X-Forwarded-Scheme',
		'X-Original-Host',
		'X-Real-Ip',
		'X-Request-Id',
		'X-Scheme',
	];

foreach ( getallheaders() as $key => $value ) {
	if ( in_array( $key, $allowed_headers ) ) {
		$headers[ $key ] = sprintf( '%s: %s', $key, $value );
	}
}

if ( ! empty( $_SERVER['X-Real-Ip'] ) ) {
	$ip = $_SERVER['X-Real-Ip'];
} elseif ( ! empty( $_SERVER['HTTP_CLIENT_IP'] ) ) {
	$ip = $_SERVER['HTTP_CLIENT_IP'];
} elseif ( ! empty( $_SERVER['HTTP_X_FORWARDED_FOR'] ) ) {
	$ip = $_SERVER['HTTP_X_FORWARDED_FOR'];
} else {
	$ip = $_SERVER['REMOTE_ADDR'];
}


if ( ! filter_var( $ip, FILTER_VALIDATE_IP ) ) {
	exit;
}

$headers['X-Forwarded-For'] = 'X-Forwarded-For: ' . $ip;

$ch = curl_init( $url );

curl_setopt( $ch, CURLOPT_HTTPHEADER, $headers );
curl_setopt( $ch, CURLOPT_FOLLOWLOCATION, true );
curl_setopt( $ch, CURLOPT_HEADER, true );
curl_setopt( $ch, CURLOPT_RETURNTRANSFER, true );
curl_setopt( $ch, CURLOPT_SSL_VERIFYHOST, false );
curl_setopt( $ch, CURLOPT_SSL_VERIFYPEER, false );

curl_setopt( $ch, CURLOPT_POST, true );
curl_setopt( $ch, CURLOPT_POSTFIELDS, file_get_contents( 'php://input' ) );

$response = curl_exec( $ch );

$header_size = curl_getinfo( $ch, CURLINFO_HEADER_SIZE );
$headers     = substr( $response, 0, $header_size );
$body        = substr( $response, $header_size );

// replace the line terminator for the current operating system with an empty string
$headers = str_replace( PHP_EOL, '', $headers );
// Tolerate line terminator: CRLF = LF (RFC 2616 19.3).
$headers = str_replace( "\r\n", "\n", $headers );
/*
 * Unfold folded header fields. LWS = [CRLF] 1*( SP | HT ) <US-ASCII SP, space (32)>,
 * <US-ASCII HT, horizontal-tab (9)> (RFC 2616 2.2).
 */
$headers = preg_replace( '/\n[ \t]/', ' ', $headers );

header( $headers );

echo $body;

curl_close( $ch );
exit;
