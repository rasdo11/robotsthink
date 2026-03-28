<?php
if ( ! defined( 'ABSPATH' ) ) exit;

class RTN_Post_Publisher {

    public static function publish_news_roundup( $articles ) {
        $author_id   = get_option( 'rtn_post_author', 1 );
        $tag_name    = get_option( 'rtn_news_tag', 'AI News' );
        $post_status = get_option( 'rtn_post_status', 'draft' );
        $date        = date( 'F j, Y' );
        $title       = 'AI News Roundup: ' . $date;

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
            'post_status'  => $post_status,
            'post_author'  => intval( $author_id ),
            'tags_input'   => array( $tag ),
        ), true );
    }

    public static function publish_think_piece( $think_piece ) {
        $author_id   = get_option( 'rtn_post_author', 1 );
        $post_status = get_option( 'rtn_post_status', 'draft' );

        // Build tag list: base tag + any tags returned by Claude
        $base_tag_name = get_option( 'rtn_thinkpiece_tag', 'Think Piece' );
        $tags          = array( self::get_or_create_tag( $base_tag_name ) );

        if ( ! empty( $think_piece['tags'] ) && is_array( $think_piece['tags'] ) ) {
            foreach ( $think_piece['tags'] as $tag_name ) {
                $tag_name = sanitize_text_field( $tag_name );
                if ( $tag_name ) {
                    $tags[] = self::get_or_create_tag( $tag_name );
                }
            }
        }

        $post_id = wp_insert_post( array(
            'post_title'   => sanitize_text_field( $think_piece['title'] ),
            'post_content' => wp_kses_post( $think_piece['content'] ),
            'post_status'  => $post_status,
            'post_author'  => intval( $author_id ),
            'tags_input'   => $tags,
        ), true );

        // Fetch and attach featured image if Unsplash key is configured
        if ( ! is_wp_error( $post_id ) && ! empty( $think_piece['featured_image_query'] ) ) {
            $unsplash_key = get_option( 'rtn_unsplash_key' );
            if ( $unsplash_key ) {
                $photo_result = RTN_Photo_Fetcher::attach_featured_image( $post_id, $think_piece['featured_image_query'], $think_piece['title'] );
                if ( is_wp_error( $photo_result ) ) {
                    error_log( '[RobotsThink] Photo fetch failed: ' . $photo_result->get_error_message() );
                }
            }
        }

        return $post_id;
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
