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

        $word_count = get_option( 'rtn_article_word_count', '900-1200' );

        $prompt = "You are a sharp, original technology writer for RobotsThink, an AI news and commentary website.\n\nBased on these recent AI headlines:\n$headlines\nWrite an original think piece article with these STRICT requirements:\n\n**Length:** $word_count words. Hit this target — not shorter, not longer.\n\n**Originality:** Do NOT summarize the news. Develop a unique, opinionated thesis that the headlines only hint at. Write from first principles. Surprise the reader with a perspective they haven't considered. Avoid clichés like 'In a world where...' or 'As AI continues to...'. Every paragraph should earn its place with a specific insight or argument, not filler.\n\n**Structure:**\n1. Hook opening (1 paragraph) — a bold claim, surprising fact, or provocative question that pulls the reader in immediately\n2. Thesis paragraph — state your specific, non-obvious argument clearly\n3. Body (3–4 sections, each with an <h2> subheading) — each section develops one aspect of the argument with concrete examples, historical parallels, or tight reasoning\n4. Conclusion (1–2 paragraphs) — crystallize the argument, don't just restate it; end with a memorable, forward-looking final line\n5. Sign off with: <p><em>— RobotsThink</em></p>\n\n**Voice:** Confident, direct, occasionally irreverent. Write for an educated general audience, not AI insiders. Define jargon when you use it. Short sentences land harder than long ones.\n\n**Tags:** Generate 3–5 specific, relevant tags for this article (e.g. 'OpenAI', 'AGI', 'AI regulation', 'Large Language Models', 'AI ethics'). Be specific — 'AI' alone is too broad.\n\n**Featured Image:** Provide a short, descriptive search query (3–5 words) that would find a compelling, relevant stock photo for this article on Unsplash. Think visually — what image would make someone stop scrolling?\n\nRespond ONLY with valid JSON in this exact format:\n{\"title\": \"Your headline here\", \"content\": \"Full article HTML using <p>, <h2>, <h3> tags\", \"tags\": [\"tag1\", \"tag2\", \"tag3\"], \"featured_image_query\": \"descriptive search query\"}";

        $response = wp_remote_post( 'https://api.anthropic.com/v1/messages', array(
            'timeout' => 60,
            'headers' => array(
                'x-api-key'         => $api_key,
                'anthropic-version' => '2023-06-01',
                'content-type'      => 'application/json',
            ),
            'body' => json_encode( array(
                'model'      => 'claude-sonnet-4-6',
                'max_tokens' => 3500,
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
