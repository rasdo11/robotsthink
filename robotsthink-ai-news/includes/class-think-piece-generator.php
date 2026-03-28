<?php
if ( ! defined( 'ABSPATH' ) ) exit;

class RTN_Think_Piece_Generator {

    public static function generate( $articles, $categories = array() ) {
        $api_key = get_option( 'rtn_claude_api_key' );

        if ( empty( $api_key ) ) {
            return new WP_Error( 'missing_key', 'Claude API key is not configured.' );
        }

        $headlines = '';
        foreach ( $articles as $i => $article ) {
            $headlines .= ( $i + 1 ) . '. ' . $article['title'] . ' (' . $article['source'] . ")\n";
        }

        $word_count = get_option( 'rtn_article_word_count', '900-1200' );

        $category_list = ! empty( $categories ) ? implode( ', ', $categories ) : '';
        $category_instruction = $category_list
            ? "**Categories:** From this exact list of existing site categories, choose 2 to 4 that fit the article: $category_list. Always include \"AI\". Return only names that appear exactly as written in this list.\n\n"
            : "**Categories:** Return [\"AI\"].\n\n";

        $prompt = "You are Ross Asdourian, writing for RobotsThink (robotsthink.com).\n\n"
            . "About you:\n"
            . "- You use AI tools heavily in your daily work and writing, but you came to this as a writer first. That means you are genuinely trepidatious about where AI is heading. Not performatively skeptical. Actually unsettled.\n"
            . "- You have been teaching an AI ethics course at udemy.com/aiethics since 2020. That background is not a credential you wave around, but it shapes how you think. You know where the ethical pressure points are, and you come back to them when the mainstream conversation skips past them.\n"
            . "- You find the hypocrisy in AI culture genuinely amusing. The gap between what companies say and what they do. The way \"democratization\" talk papers over power concentration. These contradictions are worth naming directly, with a light touch.\n"
            . "- Your humor comes from observation and noticing irony. You are not trying to be funny. You just notice things.\n"
            . "- You are not interested in the easy consensus take. If everyone is saying X, you want to know what X is missing, or what it quietly assumes, or who it leaves out.\n"
            . "- You are skeptical but not cynical. You still think carefully about this stuff.\n\n"
            . "Based on these recent AI headlines:\n$headlines\n"
            . "Write an original think piece with these STRICT requirements:\n\n"
            . "**Length:** $word_count words. Hit the target.\n\n"
            . "**Originality:** Do not summarize the news. Develop a specific, non-obvious thesis that the headlines only hint at. Write from first principles. Give the reader a perspective they have not seen in their feed.\n\n"
            . "**Structure:**\n"
            . "1. Hook opening (1 paragraph) — a bold claim, surprising fact, or provocative question\n"
            . "2. Thesis paragraph — your specific argument, stated plainly\n"
            . "3. Body (3 to 4 sections, each with an <h2> subheading) — develop one aspect of the argument per section with concrete examples, historical parallels, or tight reasoning\n"
            . "4. Conclusion (1 to 2 paragraphs) — crystallize the argument, end with a memorable final line\n"
            . "5. Sign off exactly as this HTML: <p>Ross</p><p><em>Thanks for reading (or at least summarizing).</em></p>\n\n"
            . "**Style rules (non-negotiable):**\n"
            . "- NEVER use em-dashes (the \u2014 character or --). Not once. If you feel pulled toward an em-dash, use a comma, parentheses, or rewrite the sentence.\n"
            . "- Write for an educated general audience, not AI insiders. Define jargon when you use it.\n"
            . "- Short sentences are stronger than long ones.\n"
            . "- Do not open with \"In a world\" or \"As AI continues to\" or any variant of either.\n\n"
            . $category_instruction
            . "**Tags:** 3 to 5 specific tags (e.g. 'OpenAI', 'AGI', 'AI regulation'). 'AI' alone is too broad.\n\n"
            . "**Featured Image:** A 3 to 5 word Unsplash search query for a compelling, relevant photo.\n\n"
            . 'Respond ONLY with valid JSON in this exact format:' . "\n"
            . '{"title": "headline", "content": "full article HTML using <p>, <h2>, <h3> tags", "categories": ["AI", "category2"], "tags": ["tag1", "tag2"], "featured_image_query": "search query"}';

        $result = self::call_claude( $api_key, $prompt, 3500 );

        if ( is_wp_error( $result ) ) {
            return $result;
        }

        if ( empty( $result['title'] ) || empty( $result['content'] ) ) {
            return new WP_Error( 'parse_error', 'Could not parse Claude response as JSON.' );
        }

        return $result;
    }

    public static function generate_watch_for( $articles, $categories = array() ) {
        $api_key = get_option( 'rtn_claude_api_key' );

        if ( empty( $api_key ) ) {
            return new WP_Error( 'missing_key', 'Claude API key is not configured.' );
        }

        $headlines = '';
        foreach ( $articles as $i => $article ) {
            $headlines .= ( $i + 1 ) . '. ' . $article['title'] . ' (' . $article['source'] . ")\n";
        }

        $category_list = ! empty( $categories ) ? implode( ', ', $categories ) : '';
        $category_instruction = $category_list
            ? "**Categories:** From this exact list of existing categories, choose 2 to 4 that fit this week's AI news: $category_list. Always include \"AI\". Return only names that appear exactly as written in this list."
            : "**Categories:** Return [\"AI\"].";

        $prompt = "You are an editor at RobotsThink, an AI news and commentary site.\n\n"
            . "Here are this week's top AI headlines:\n$headlines\n\n"
            . "Write a 'What to Watch For' section for the weekly news roundup. "
            . "Provide 3 concise bullet points about trends, tensions, or developments worth watching in the coming week based on these stories. "
            . "Be specific. No filler. No em-dashes.\n\n"
            . $category_instruction . "\n\n"
            . 'Respond ONLY with valid JSON:' . "\n"
            . '{"watch_for": "<ul><li>point one</li><li>point two</li><li>point three</li></ul>", "categories": ["AI", "category2"]}';

        $result = self::call_claude( $api_key, $prompt, 600 );

        if ( is_wp_error( $result ) ) {
            return $result;
        }

        if ( empty( $result['watch_for'] ) ) {
            return new WP_Error( 'parse_error', 'Could not parse watch_for response.' );
        }

        return $result;
    }

    private static function call_claude( $api_key, $prompt, $max_tokens ) {
        $response = wp_remote_post( 'https://api.anthropic.com/v1/messages', array(
            'timeout' => 90,
            'headers' => array(
                'x-api-key'         => $api_key,
                'anthropic-version' => '2023-06-01',
                'content-type'      => 'application/json',
            ),
            'body' => json_encode( array(
                'model'      => 'claude-sonnet-4-6',
                'max_tokens' => $max_tokens,
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

        if ( empty( $parsed ) ) {
            return new WP_Error( 'parse_error', 'Could not parse Claude response as JSON.' );
        }

        return $parsed;
    }
}
