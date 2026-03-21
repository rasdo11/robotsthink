<?php
if ( ! defined( 'ABSPATH' ) ) exit;

class RTN_Scheduler {

    const CRON_HOOK     = 'rtn_auto_post_event';
    const CRON_INTERVAL = 'rtn_every_two_days';

    public static function init() {
        add_filter( 'cron_schedules', array( __CLASS__, 'add_interval' ) );
        add_action( self::CRON_HOOK, array( __CLASS__, 'run_cycle' ) );
    }

    public static function add_interval( $schedules ) {
        $schedules[ self::CRON_INTERVAL ] = array(
            'interval' => 2 * DAY_IN_SECONDS,
            'display'  => 'Every Two Days',
        );
        return $schedules;
    }

    public static function activate() {
        if ( ! wp_next_scheduled( self::CRON_HOOK ) ) {
            wp_schedule_event( time(), self::CRON_INTERVAL, self::CRON_HOOK );
        }
    }

    public static function deactivate() {
        $timestamp = wp_next_scheduled( self::CRON_HOOK );
        if ( $timestamp ) {
            wp_unschedule_event( $timestamp, self::CRON_HOOK );
        }
    }

    /**
     * Main cycle: fetch news, generate think piece, publish both.
     *
     * @return array Result with 'success' bool and 'message' string.
     */
    public static function run_cycle() {
        $topic    = get_option( 'rtn_news_category', 'artificial intelligence' );
        $articles = RTN_News_Fetcher::fetch( $topic, 5 );

        if ( is_wp_error( $articles ) ) {
            error_log( '[RobotsThink] News fetch failed: ' . $articles->get_error_message() );
            return array( 'success' => false, 'message' => 'News fetch failed: ' . $articles->get_error_message() );
        }

        // Publish news roundup
        $news_post_id = RTN_Post_Publisher::publish_news_roundup( $articles );
        if ( is_wp_error( $news_post_id ) ) {
            error_log( '[RobotsThink] News roundup publish failed: ' . $news_post_id->get_error_message() );
            return array( 'success' => false, 'message' => 'News roundup publish failed: ' . $news_post_id->get_error_message() );
        }

        // Generate think piece
        $think_piece = RTN_Think_Piece_Generator::generate( $articles );
        if ( is_wp_error( $think_piece ) ) {
            error_log( '[RobotsThink] Think piece generation failed: ' . $think_piece->get_error_message() );
            return array( 'success' => false, 'message' => 'Think piece generation failed: ' . $think_piece->get_error_message() );
        }

        // Publish think piece
        $think_post_id = RTN_Post_Publisher::publish_think_piece( $think_piece );
        if ( is_wp_error( $think_post_id ) ) {
            error_log( '[RobotsThink] Think piece publish failed: ' . $think_post_id->get_error_message() );
            return array( 'success' => false, 'message' => 'Think piece publish failed: ' . $think_post_id->get_error_message() );
        }

        error_log( '[RobotsThink] Cycle complete. News post #' . $news_post_id . ', Think piece #' . $think_post_id );
        return array(
            'success' => true,
            'message' => 'Published news roundup (post #' . $news_post_id . ') and think piece (post #' . $think_post_id . ').',
        );
    }
}
