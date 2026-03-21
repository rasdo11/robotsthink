<?php
if ( ! defined( 'ABSPATH' ) ) exit;

class RTN_Think_Piece_Generator {

    /**
     * Generate a think piece article using Claude AI based on recent news.
     *
     * @param array $articles Array of news articles.
     * @return array|WP_Error Array with 'title' and 'content', or WP_Error.
     */
    public static function generate( $articles ) {
        $api_key = get_option( 'rtn_claude_api_key' );

        if ( empty( $api_key ) ) {
            return new WP_Error( 'missing_key', 'Claude API key is not configured.' );
        }

        // Build a summary of recent headlines for context
        $headlines = '';
        foreach ( $articles as $i => $article ) {
            $headlines .= ( $i + 1 ) . '. ' . $article['title'] . ' (' . $article['source'] . ")\n";
        }

        $prompt = <<<EOT
You are a sharp, thoughtful technology writer for RobotsThink, an AI news and commentary website.

Based on these recent AI headlines:
$headlines

Write an original think piece article (600-900 words) that:
- Has a compelling, specific headline (not generic)
- Explores a deeper angle, implication, or trend suggested by these headlines
- Has a clear thesis and opinionated voice — don't be wishy-washy
- Is structured with a hook intro, 2-3 body sections, and a punchy conclusion
- Is written in engaging, accessible prose (not academic)
- Includes the tag line "— RobotsThink" at the end

Respond ONLY with valid JSON in this exact format:
{
  "title": "Your article headline here",
  "content": "Full article HTML content here using <p>, <h2>, <h3> tags"
}
EOT;

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

        // Strip markdown code fences if present
        $text = preg_replace( '/^```json\s*/i', '', trim( $text ) );
        $text = preg_replace( '/\s*```$/', '', $text );

        $parsed = json_decode( $text, true );

        if ( empty( $parsed['title'] ) || empty( $parsed['content'] ) ) {
            return new WP_Error( 'parse_error', 'Could not parse Claude response as JSON.' );
        }

        return $parsed;
    }
}
