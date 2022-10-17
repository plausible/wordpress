<?php

$url = "https://plausible.io/api/event";

$headers = array();

$allowed_headers =

	array(
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
	);

foreach ( getallheaders() as $key => $value ) {
	if ( in_array( $key, $allowed_headers ) ) {
		$headers[ $key ] = sprintf( '%s: %s', $key, $value );
	}
}


if ( ! empty( $_SERVER['HTTP_CLIENT_IP'] ) ) {
	$ip = $_SERVER['HTTP_CLIENT_IP'];
} elseif ( ! empty( $_SERVER['HTTP_X_FORWARDED_FOR'] ) ) {
	$ip = $_SERVER['HTTP_X_FORWARDED_FOR'];
} else {
	$ip = $_SERVER['REMOTE_ADDR'];
}

$headers['X-Forwarded-For'] = 'X-Forwarded-For: ' . $headers['X-Real-Ip'];

$ch = curl_init( $url );

curl_setopt( $ch, CURLOPT_HTTPHEADER, $headers );
curl_setopt( $ch, CURLOPT_FOLLOWLOCATION, true );
curl_setopt( $ch, CURLOPT_HEADER, false );
curl_setopt( $ch, CURLOPT_RETURNTRANSFER, true );
curl_setopt( $ch, CURLOPT_SSL_VERIFYHOST, false );
curl_setopt( $ch, CURLOPT_SSL_VERIFYPEER, false );


curl_setopt( $ch, CURLOPT_POST, true );
curl_setopt( $ch, CURLOPT_POSTFIELDS, file_get_contents( 'php://input' ) );

curl_exec( $ch );

curl_close( $ch );

exit;
