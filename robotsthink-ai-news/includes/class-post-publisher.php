<?php
if ( ! defined( 'ABSPATH' ) ) exit;

class RTN_Post_Publisher {

    public static function publish_news_roundup( $articles ) {
        $author_id = get_option( 'rtn_post_author', 1 );
        $tag_name  = get_option( 'rtn_news_tag', 'AI News' );
        $date      = date( 'F j, Y' );
        $title     = 'AI News Roundup: ' . $date;

        $content = '<p>Here are the top AI stories making headlines today.</p>' . "\n\n";

        foreach ( $articles as $article ) {
            $content .= '<h3>' . esc_html( $article['title'] ) . '</h3>' . "\n";
            if ( ! empty( $article['description'] ) ) {
                $content .= '<p>' . esc_html( $article['description'] ) . '</p>' . "\n";
            }
            $content .= '<p><a href="' . esc_url( $article['url'] ) . '" target="_blank" rel="noopener noreferrer">Read more &rarr; ' . esc_html( $article['source'] ) . '</a></p>' . "\n\n";
        }

        $tag = self::get_or_create_tag( $tag_name );

        return wp_insert_post( array(
            'post_title'   => $title,
            'post_content' => $content,
            'post_status'  => 'publish',
            'post_author'  => intval( $author_id ),
            'tags_input'   => array( $tag ),
        ), true );
    }

    public static function publish_think_piece( $think_piece ) {
        $author_id = get_option( 'rtn_post_author', 1 );
        $tag_name  = get_option( 'rtn_thinkpiece_tag', 'Think Piece' );
        $tag       = self::get_or_create_tag( $tag_name );

        return wp_insert_post( array(
            'post_title'   => sanitize_text_field( $think_piece['title'] ),
            'post_content' => wp_kses_post( $think_piece['content'] ),
            'post_status'  => 'publish',
            'post_author'  => intval( $author_id ),
            'tags_input'   => array( $tag ),
        ), true );
    }

    private static function get_or_create_tag( $name ) {
        $term = get_term_by( 'name', $name, 'post_tag' );
        if ( $term ) return $term->slug;

        $result = wp_insert_term( $name, 'post_tag' );
        if ( is_wp_error( $result ) ) return sanitize_title( $name );

        $term = get_term( $result['term_id'], 'post_tag' );
        return $term->slug;
    }
}
