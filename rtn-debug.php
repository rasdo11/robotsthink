<?php
/**
 * RobotsThink Unsplash Diagnostic
 * Upload to your WordPress root, visit in browser, then DELETE immediately.
 *
 * Usage: https://robotsthink.com/rtn-debug.php
 */

// Load WordPress
require_once __DIR__ . '/wp-load.php';

// Only allow admins
if ( ! current_user_can( 'manage_options' ) ) {
    wp_die( 'Not allowed.' );
}

$api_key = get_option( 'rtn_unsplash_key' );
$query   = 'artificial intelligence robot';

echo '<pre>';
echo "=== RobotsThink Unsplash Diagnostic ===\n\n";
echo "Unsplash key configured: " . ( $api_key ? 'YES (' . substr( $api_key, 0, 6 ) . '...)' : 'NO' ) . "\n\n";

if ( ! $api_key ) {
    echo "ERROR: No Unsplash key found in options.\n";
    echo '</pre>';
    exit;
}

// Step 1: Call Unsplash API
$url = add_query_arg( array(
    'query'       => urlencode( $query ),
    'orientation' => 'landscape',
    'per_page'    => 1,
), 'https://api.unsplash.com/photos/random' );

echo "1. Calling Unsplash API...\n";
echo "   Request URL: $url\n\n";

$response = wp_remote_get( $url, array(
    'timeout' => 15,
    'headers' => array(
        'Authorization' => 'Client-ID ' . $api_key,
    ),
) );

if ( is_wp_error( $response ) ) {
    echo "ERROR: wp_remote_get failed: " . $response->get_error_message() . "\n";
    echo '</pre>';
    exit;
}

$http_code = wp_remote_retrieve_response_code( $response );
echo "   HTTP status: $http_code\n\n";

$body = json_decode( wp_remote_retrieve_body( $response ), true );

if ( $http_code !== 200 ) {
    echo "ERROR: Unsplash returned non-200.\n";
    echo "   Response body:\n";
    print_r( $body );
    echo '</pre>';
    exit;
}

if ( empty( $body['urls']['regular'] ) ) {
    echo "ERROR: No urls.regular in response.\n";
    echo "   Keys in response: " . implode( ', ', array_keys( $body ) ) . "\n";
    if ( ! empty( $body['urls'] ) ) {
        echo "   Keys in urls: " . implode( ', ', array_keys( $body['urls'] ) ) . "\n";
    }
    echo '</pre>';
    exit;
}

$image_url = $body['urls']['regular'];
echo "2. Image URL returned by Unsplash:\n";
echo "   $image_url\n\n";

// Step 2: Test esc_url_raw
$escaped_url = esc_url_raw( $image_url );
echo "3. After esc_url_raw():\n";
echo "   $escaped_url\n";
echo "   Changed: " . ( $image_url !== $escaped_url ? 'YES' : 'no' ) . "\n\n";

// Step 3: Test wp_http_validate_url
$validated = wp_http_validate_url( $image_url );
echo "4. wp_http_validate_url( original ):\n";
echo "   " . ( $validated ? "PASS: $validated" : "FAIL (returns false)" ) . "\n\n";

$validated_escaped = wp_http_validate_url( $escaped_url );
echo "5. wp_http_validate_url( esc_url_raw'd ):\n";
echo "   " . ( $validated_escaped ? "PASS: $validated_escaped" : "FAIL (returns false)" ) . "\n\n";

// Step 4: Try actually downloading the image
echo "6. Attempting wp_remote_get on image URL...\n";
$img_response = wp_remote_get( $image_url, array( 'timeout' => 15 ) );
if ( is_wp_error( $img_response ) ) {
    echo "   FAIL: " . $img_response->get_error_message() . "\n";
} else {
    $img_code = wp_remote_retrieve_response_code( $img_response );
    $content_type = wp_remote_retrieve_header( $img_response, 'content-type' );
    echo "   HTTP status: $img_code\n";
    echo "   Content-Type: $content_type\n";
}

echo "\n=== Done ===\n";
echo '</pre>';
