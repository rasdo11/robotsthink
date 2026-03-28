<?php
if ( ! defined( 'ABSPATH' ) ) exit;

class RTN_Photo_Fetcher {

    /**
     * Fetch a photo from Unsplash matching the query, download it to the
     * WordPress media library, and set it as the featured image of $post_id.
     *
     * @param int    $post_id   WordPress post ID to attach the image to.
     * @param string $query     Search query (e.g. "robot hand keyboard").
     * @param string $alt_text  Alt text / title for the attachment.
     * @return int|WP_Error     Attachment ID on success, WP_Error on failure.
     */
    public static function attach_featured_image( $post_id, $query, $alt_text = '' ) {
        $api_key = get_option( 'rtn_unsplash_key' );

        if ( empty( $api_key ) ) {
            return new WP_Error( 'missing_key', 'Unsplash API key is not configured.' );
        }

        // Search Unsplash for a landscape photo matching the query
        $url = add_query_arg( array(
            'query'       => urlencode( sanitize_text_field( $query ) ),
            'orientation' => 'landscape',
            'per_page'    => 1,
        ), 'https://api.unsplash.com/photos/random' );

        $response = wp_remote_get( $url, array(
            'timeout' => 15,
            'headers' => array(
                'Authorization' => 'Client-ID ' . $api_key,
            ),
        ) );

        if ( is_wp_error( $response ) ) {
            return $response;
        }

        $photo = json_decode( wp_remote_retrieve_body( $response ), true );

        if ( empty( $photo['urls']['regular'] ) ) {
            return new WP_Error( 'unsplash_error', 'No photo returned from Unsplash for query: ' . $query );
        }

        $image_url   = esc_url_raw( $photo['urls']['regular'] );
        $photographer = ! empty( $photo['user']['name'] ) ? $photo['user']['name'] : 'Unsplash';
        $photo_link   = ! empty( $photo['links']['html'] ) ? $photo['links']['html'] : 'https://unsplash.com';

        // Download image into WP media library and attach to post
        require_once ABSPATH . 'wp-admin/includes/media.php';
        require_once ABSPATH . 'wp-admin/includes/file.php';
        require_once ABSPATH . 'wp-admin/includes/image.php';

        $attachment_id = media_sideload_image( $image_url, $post_id, sanitize_text_field( $alt_text ), 'id' );

        if ( is_wp_error( $attachment_id ) ) {
            return $attachment_id;
        }

        set_post_thumbnail( $post_id, $attachment_id );

        // Store photo credit as post meta for display in theme
        update_post_meta( $post_id, '_rtn_photo_credit', $photographer );
        update_post_meta( $post_id, '_rtn_photo_link', $photo_link );

        return $attachment_id;
    }
}
