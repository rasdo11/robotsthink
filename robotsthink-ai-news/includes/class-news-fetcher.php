<?php
if ( ! defined( 'ABSPATH' ) ) exit;

class RTN_News_Fetcher {

    public static function fetch( $topic = 'artificial intelligence', $count = 5 ) {
        $api_key = get_option( 'rtn_newsapi_key' );

        if ( empty( $api_key ) ) {
            return new WP_Error( 'missing_key', 'NewsAPI key is not configured.' );
        }

        $url = add_query_arg( array(
            'q'        => urlencode( $topic ),
            'language' => 'en',
            'sortBy'   => 'publishedAt',
            'pageSize' => intval( $count ),
            'apiKey'   => $api_key,
        ), 'https://newsapi.org/v2/everything' );

        $response = wp_remote_get( $url, array( 'timeout' => 15 ) );

        if ( is_wp_error( $response ) ) {
            return $response;
        }

        $body = json_decode( wp_remote_retrieve_body( $response ), true );

        if ( empty( $body['articles'] ) ) {
            return new WP_Error( 'no_articles', 'No articles returned from NewsAPI.' );
        }

        $articles = array();
        foreach ( $body['articles'] as $article ) {
            if ( empty( $article['title'] ) || $article['title'] === '[Removed]' ) continue;
            $articles[] = array(
                'title'       => $article['title'],
                'description' => $article['description'] ?? '',
                'url'         => $article['url'],
                'source'      => $article['source']['name'] ?? 'Unknown',
                'published'   => $article['publishedAt'] ?? '',
            );
        }

        return $articles;
    }
}
