<?php
if ( ! defined( 'ABSPATH' ) ) exit;

class RTN_Settings {

    public static function init() {
        add_action( 'admin_menu', array( __CLASS__, 'add_menu' ) );
        add_action( 'admin_init', array( __CLASS__, 'register_settings' ) );
    }

    public static function add_menu() {
        add_options_page(
            'RobotsThink AI News',
            'RobotsThink AI',
            'manage_options',
            'robotsthink-ai-news',
            array( __CLASS__, 'render_page' )
        );
    }

    public static function register_settings() {
        register_setting( 'rtn_settings_group', 'rtn_guardian_api_key',   array( 'sanitize_callback' => 'sanitize_text_field' ) );
        register_setting( 'rtn_settings_group', 'rtn_claude_api_key',     array( 'sanitize_callback' => 'sanitize_text_field' ) );
        register_setting( 'rtn_settings_group', 'rtn_unsplash_key',       array( 'sanitize_callback' => 'sanitize_text_field' ) );
        register_setting( 'rtn_settings_group', 'rtn_news_category',      array( 'sanitize_callback' => 'sanitize_text_field' ) );
        register_setting( 'rtn_settings_group', 'rtn_post_author',        array( 'sanitize_callback' => 'absint' ) );
        register_setting( 'rtn_settings_group', 'rtn_post_status',        array( 'sanitize_callback' => 'sanitize_text_field' ) );
        register_setting( 'rtn_settings_group', 'rtn_article_word_count', array( 'sanitize_callback' => 'sanitize_text_field' ) );
        register_setting( 'rtn_settings_group', 'rtn_news_tag',           array( 'sanitize_callback' => 'sanitize_text_field' ) );
        register_setting( 'rtn_settings_group', 'rtn_thinkpiece_tag',     array( 'sanitize_callback' => 'sanitize_text_field' ) );
    }

    public static function render_page() {
        if ( ! current_user_can( 'manage_options' ) ) return;
        $users = get_users( array( 'role__in' => array( 'administrator', 'editor', 'author' ) ) );
        ?>
        <div class="wrap">
            <h1>RobotsThink AI News Settings</h1>
            <form method="post" action="options.php">
                <?php settings_fields( 'rtn_settings_group' ); ?>
                <table class="form-table">
                    <tr>
                        <th>Guardian API Key</th>
                        <td>
                            <input type="password" name="rtn_guardian_api_key" value="<?php echo esc_attr( get_option( 'rtn_guardian_api_key' ) ); ?>" class="regular-text" />
                            <p class="description">Get a free key at <a href="https://open-platform.theguardian.com/access/" target="_blank">open-platform.theguardian.com</a></p>
                        </td>
                    </tr>
                    <tr>
                        <th>Claude API Key</th>
                        <td>
                            <input type="password" name="rtn_claude_api_key" value="<?php echo esc_attr( get_option( 'rtn_claude_api_key' ) ); ?>" class="regular-text" />
                            <p class="description">Get your key at <a href="https://console.anthropic.com" target="_blank">console.anthropic.com</a></p>
                        </td>
                    </tr>
                    <tr>
                        <th>Unsplash API Key</th>
                        <td>
                            <input type="password" name="rtn_unsplash_key" value="<?php echo esc_attr( get_option( 'rtn_unsplash_key' ) ); ?>" class="regular-text" />
                            <p class="description">Optional. Get a free key at <a href="https://unsplash.com/developers" target="_blank">unsplash.com/developers</a>. When set, a featured photo is automatically fetched and attached to each think piece.</p>
                        </td>
                    </tr>
                    <tr>
                        <th>News Search Topic</th>
                        <td>
                            <input type="text" name="rtn_news_category" value="<?php echo esc_attr( get_option( 'rtn_news_category', 'artificial intelligence' ) ); ?>" class="regular-text" />
                            <p class="description">e.g. "artificial intelligence", "AI", "machine learning"</p>
                        </td>
                    </tr>
                    <tr>
                        <th>Post Status</th>
                        <td>
                            <select name="rtn_post_status">
                                <?php $current_status = get_option( 'rtn_post_status', 'draft' ); ?>
                                <option value="draft"   <?php selected( $current_status, 'draft' ); ?>>Draft (review before publishing)</option>
                                <option value="publish" <?php selected( $current_status, 'publish' ); ?>>Publish immediately</option>
                            </select>
                            <p class="description">Default is <strong>Draft</strong> — posts go to your queue for review before going live.</p>
                        </td>
                    </tr>
                    <tr>
                        <th>Article Word Count</th>
                        <td>
                            <input type="text" name="rtn_article_word_count" value="<?php echo esc_attr( get_option( 'rtn_article_word_count', '900-1200' ) ); ?>" class="small-text" />
                            <p class="description">Target word count range for think piece articles (e.g. <code>900-1200</code> or <code>1000-1500</code>).</p>
                        </td>
                    </tr>
                    <tr>
                        <th>Post Author</th>
                        <td>
                            <select name="rtn_post_author">
                                <?php foreach ( $users as $user ) : ?>
                                    <option value="<?php echo esc_attr( $user->ID ); ?>" <?php selected( get_option( 'rtn_post_author' ), $user->ID ); ?>>
                                        <?php echo esc_html( $user->display_name ); ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </td>
                    </tr>
                    <tr>
                        <th>News Post Tag</th>
                        <td>
                            <input type="text" name="rtn_news_tag" value="<?php echo esc_attr( get_option( 'rtn_news_tag', 'AI News' ) ); ?>" class="regular-text" />
                        </td>
                    </tr>
                    <tr>
                        <th>Think Piece Tag</th>
                        <td>
                            <input type="text" name="rtn_thinkpiece_tag" value="<?php echo esc_attr( get_option( 'rtn_thinkpiece_tag', 'Think Piece' ) ); ?>" class="regular-text" />
                        </td>
                    </tr>
                </table>
                <?php submit_button(); ?>
            </form>

            <hr />
            <h2>Manual Trigger</h2>
            <p>Run the auto-post cycle right now (for testing):</p>
            <form method="post">
                <?php wp_nonce_field( 'rtn_manual_run', 'rtn_nonce' ); ?>
                <input type="hidden" name="rtn_manual_run" value="1" />
                <?php submit_button( 'Run Now', 'secondary' ); ?>
            </form>
            <?php
            if ( isset( $_POST['rtn_manual_run'] ) && check_admin_referer( 'rtn_manual_run', 'rtn_nonce' ) ) {
                $result = RTN_Scheduler::run_cycle();
                echo '<div class="notice notice-' . ( $result['success'] ? 'success' : 'error' ) . '"><p>' . esc_html( $result['message'] ) . '</p></div>';
            }
            ?>

            <hr />
            <h2>Next Scheduled Run</h2>
            <?php
            $next = wp_next_scheduled( 'rtn_auto_post_event' );
            if ( $next ) {
                echo '<p>Next run: <strong>' . esc_html( get_date_from_gmt( date( 'Y-m-d H:i:s', $next ), 'F j, Y g:i a' ) ) . '</strong></p>';
            } else {
                echo '<p>Not scheduled. Deactivate and reactivate the plugin to reschedule.</p>';
            }
            ?>
        </div>
        <?php
    }
}
