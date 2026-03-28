<?php
if ( ! defined( 'ABSPATH' ) ) exit;

class RTN_Scheduler {

    const NEWS_CRON_HOOK  = 'rtn_news_event';
    const THINK_CRON_HOOK = 'rtn_think_piece_event';

    public static function init() {
        add_action( self::NEWS_CRON_HOOK,  array( __CLASS__, 'run_news_cycle' ) );
        add_action( self::THINK_CRON_HOOK, array( __CLASS__, 'run_think_piece_cycle' ) );
    }

    public static function activate() {
        // Remove legacy single-cycle hook if present
        $old = wp_next_scheduled( 'rtn_auto_post_event' );
        if ( $old ) {
            wp_unschedule_event( $old, 'rtn_auto_post_event' );
        }

        // News roundup: every Monday at 8am
        if ( ! wp_next_scheduled( self::NEWS_CRON_HOOK ) ) {
            wp_schedule_event( self::next_weekday_at_8am( 1 ), 'weekly', self::NEWS_CRON_HOOK );
        }

        // Think piece: every Wednesday at 8am
        if ( ! wp_next_scheduled( self::THINK_CRON_HOOK ) ) {
            wp_schedule_event( self::next_weekday_at_8am( 3 ), 'weekly', self::THINK_CRON_HOOK );
        }
    }

    public static function deactivate() {
        foreach ( array( self::NEWS_CRON_HOOK, self::THINK_CRON_HOOK, 'rtn_auto_post_event' ) as $hook ) {
            $ts = wp_next_scheduled( $hook );
            if ( $ts ) {
                wp_unschedule_event( $ts, $hook );
            }
        }
    }

    /**
     * Returns a Unix timestamp for the next occurrence of a given ISO weekday at 8:00am local time.
     * $iso_weekday: 1=Mon, 2=Tue, 3=Wed, 4=Thu, 5=Fri, 6=Sat, 7=Sun
     * If today is that weekday, schedules for next week.
     */
    private static function next_weekday_at_8am( $iso_weekday ) {
        $now   = current_time( 'timestamp' );
        $today = (int) date( 'N', $now );
        $diff  = ( $iso_weekday - $today + 7 ) % 7;
        if ( $diff === 0 ) {
            $diff = 7;
        }
        $target = strtotime( "+{$diff} days", $now );
        return mktime( 8, 0, 0, (int) date( 'n', $target ), (int) date( 'j', $target ), (int) date( 'Y', $target ) );
    }

    private static function get_category_names() {
        $cats = get_categories( array( 'hide_empty' => false ) );
        return array_values( array_filter(
            wp_list_pluck( $cats, 'name' ),
            function( $name ) {
                return strtolower( $name ) !== 'uncategorized';
            }
        ) );
    }

    public static function run_news_cycle() {
        $topic    = get_option( 'rtn_news_category', 'artificial intelligence' );
        $articles = RTN_News_Fetcher::fetch( $topic, 5 );

        if ( is_wp_error( $articles ) ) {
            return array( 'success' => false, 'message' => 'News fetch failed: ' . $articles->get_error_message() );
        }

        $categories     = self::get_category_names();
        $watch_for_data = RTN_Think_Piece_Generator::generate_watch_for( $articles, $categories );

        if ( is_wp_error( $watch_for_data ) ) {
            // Non-fatal: publish roundup without the watch-for section
            $watch_for_data = array( 'watch_for' => '', 'categories' => array() );
        }

        $news_post_id = RTN_Post_Publisher::publish_news_roundup( $articles, $watch_for_data );

        if ( is_wp_error( $news_post_id ) ) {
            return array( 'success' => false, 'message' => 'News roundup publish failed: ' . $news_post_id->get_error_message() );
        }

        return array( 'success' => true, 'message' => 'Published news roundup (post #' . $news_post_id . ').' );
    }

    public static function run_think_piece_cycle() {
        $topic    = get_option( 'rtn_news_category', 'artificial intelligence' );
        $articles = RTN_News_Fetcher::fetch( $topic, 5 );

        if ( is_wp_error( $articles ) ) {
            return array( 'success' => false, 'message' => 'News fetch failed: ' . $articles->get_error_message() );
        }

        $categories  = self::get_category_names();
        $think_piece = RTN_Think_Piece_Generator::generate( $articles, $categories );

        if ( is_wp_error( $think_piece ) ) {
            return array( 'success' => false, 'message' => 'Think piece generation failed: ' . $think_piece->get_error_message() );
        }

        $think_post_id = RTN_Post_Publisher::publish_think_piece( $think_piece );

        if ( is_wp_error( $think_post_id ) ) {
            $code = $think_post_id->get_error_code();
            $msg  = $think_post_id->get_error_message();
            if ( $code === 'photo_failed' ) {
                return array( 'success' => true, 'message' => 'Think piece published, but: ' . $msg );
            }
            return array( 'success' => false, 'message' => 'Think piece publish failed: ' . $msg );
        }

        return array( 'success' => true, 'message' => 'Published think piece (post #' . $think_post_id . ').' );
    }
}
