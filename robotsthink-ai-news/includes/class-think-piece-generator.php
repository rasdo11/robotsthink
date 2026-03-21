<?php
if ( ! defined( 'ABSPATH' ) ) exit;

class RTN_Think_Piece_Generator {

    public static function generate( $articles ) {
        $api_key = get_option( 'rtn_claude_api_key' );

        if ( empty( $api_key ) ) {
            return new WP_Error( 'missing_key', 'Claude API key is not configured.' );
        }

        $headlines = '';
        foreach ( $articles as $i => $article ) {
            $headlines .= ( $i + 1 ) . '. ' . $article['title'] . ' (' . $article['source'] . ")\n";
        }

        $prompt = "You are a sharp, thoughtful technology writer for RobotsThink, an AI news and commentary website.\n\nBased on these recent AI headlines:\n$headlines\nWrite an original think piece article (600-900 words) that:\n- Has a compelling, specific headline\n- Explores a deeper angle or trend suggested by these headlines\n- Has a clear thesis and opinionated voice\n- Is structured with a hook intro, 2-3 body sections, and a punchy conclusion\n- Is written in engaging, accessible prose\n- Ends with the tag line '— RobotsThink'\n\nRespond ONLY with valid JSON in this exact format:\n{\"title\": \"Your headline here\", \"content\": \"Full article HTML using <p>, <h2>, <h3> tags\"}";

        $response = wp_remote_post( 'https://api.anthropic.com/v1/messages', array(
            'timeout' => 60,
            'headers' => array(
                'x-api-key'         => $api_key,
                'anthropic-version' => '2023-06-01',
                'content-type'      => 'application/json',
            ),
            'body' => json_encode( array(
                'model'      => 'claude-sonnet-4-6',
                'max_tokens' => 2000,
                'messages'   => array(
                    array( 'role' => 'user', 'content' => $prompt ),
                ),
            ) ),
        ) );

        if ( is_wp_error( $response ) ) {
            return $response;
        }

        $body = json_decode( wp_remote_retrieve_body( $response ), true );

        if ( empty( $body['content'][0]['text'] ) ) {
            return new WP_Error( 'claude_error', 'Claude returned an unexpected response.' );
        }

        $text = $body['content'][0]['text'];
        $text = preg_replace( '/^```json\s*/i', '', trim( $text ) );
        $text = preg_replace( '/\s*```$/', '', $text );

        $parsed = json_decode( $text, true );

        if ( empty( $parsed['title'] ) || empty( $parsed['content'] ) ) {
            return new WP_Error( 'parse_error', 'Could not parse Claude response as JSON.' );
        }

        return $parsed;
    }
}
