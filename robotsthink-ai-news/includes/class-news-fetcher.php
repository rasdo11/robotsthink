<?php
if ( ! defined( 'ABSPATH' ) ) exit;

class RTN_News_Fetcher {

    public static function fetch( $topic = 'artificial intelligence', $count = 5 ) {
        $api_key = get_option( 'rtn_guardian_api_key' );

        if ( empty( $api_key ) ) {
            return new WP_Error( 'missing_key', 'Guardian API key is not configured.' );
        }

        $url = add_query_arg( array(
            'q'            => urlencode( $topic ),
            'lang'         => 'en',
            'order-by'     => 'newest',
            'page-size'    => intval( $count ),
            'show-fields'  => 'trailText,publication',
            'api-key'      => $api_key,
        ), 'https://content.guardianapis.com/search' );

        $response = wp_remote_get( $url, array( 'timeout' => 15 ) );

        if ( is_wp_error( $response ) ) {
            return $response;
        }

        $body = json_decode( wp_remote_retrieve_body( $response ), true );

        if ( empty( $body['response']['results'] ) ) {
            return new WP_Error( 'no_articles', 'No articles returned from The Guardian.' );
        }

        $articles = array();
        foreach ( $body['response']['results'] as $article ) {
            if ( empty( $article['webTitle'] ) ) continue;
            $articles[] = array(
                'title'       => $article['webTitle'],
                'description' => $article['fields']['trailText'] ?? '',
                'url'         => $article['webUrl'],
                'source'      => 'The Guardian',
                'published'   => $article['webPublicationDate'] ?? '',
            );
        }

        return $articles;
    }
}
