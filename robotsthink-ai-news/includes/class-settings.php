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
            <p>Run either cycle right now:</p>
            <form method="post" style="display:inline-block; margin-right:12px;">
                <?php wp_nonce_field( 'rtn_run_news', 'rtn_news_nonce' ); ?>
                <input type="hidden" name="rtn_run_news" value="1" />
                <?php submit_button( 'Run News Roundup', 'secondary', 'rtn_run_news_btn', false ); ?>
            </form>
            <form method="post" style="display:inline-block;">
                <?php wp_nonce_field( 'rtn_run_think_piece', 'rtn_think_nonce' ); ?>
                <input type="hidden" name="rtn_run_think_piece" value="1" />
                <?php submit_button( 'Run Think Piece', 'secondary', 'rtn_run_think_btn', false ); ?>
            </form>
            <?php
            if ( isset( $_POST['rtn_run_news'] ) && check_admin_referer( 'rtn_run_news', 'rtn_news_nonce' ) ) {
                $result = RTN_Scheduler::run_news_cycle();
                echo '<div class="notice notice-' . ( $result['success'] ? 'success' : 'error' ) . '"><p>' . esc_html( $result['message'] ) . '</p></div>';
            }
            if ( isset( $_POST['rtn_run_think_piece'] ) && check_admin_referer( 'rtn_run_think_piece', 'rtn_think_nonce' ) ) {
                $result = RTN_Scheduler::run_think_piece_cycle();
                echo '<div class="notice notice-' . ( $result['success'] ? 'success' : 'error' ) . '"><p>' . esc_html( $result['message'] ) . '</p></div>';
            }
            ?>

            <hr />
            <h2>Test Unsplash Connection</h2>
            <p>Check whether your Unsplash key is working and that WordPress can download the image:</p>
            <form method="post">
                <?php wp_nonce_field( 'rtn_test_unsplash', 'rtn_unsplash_nonce' ); ?>
                <input type="hidden" name="rtn_test_unsplash" value="1" />
                <?php submit_button( 'Test Unsplash', 'secondary' ); ?>
            </form>
            <?php
            if ( isset( $_POST['rtn_test_unsplash'] ) && check_admin_referer( 'rtn_test_unsplash', 'rtn_unsplash_nonce' ) ) {
                $lines = array();
                $api_key = get_option( 'rtn_unsplash_key' );

                if ( empty( $api_key ) ) {
                    echo '<div class="notice notice-error"><p>No Unsplash key configured.</p></div>';
                } else {
                    $url = add_query_arg( array(
                        'query'       => 'artificial+intelligence',
                        'orientation' => 'landscape',
                        'per_page'    => 1,
                    ), 'https://api.unsplash.com/photos/random' );

                    $response = wp_remote_get( $url, array(
                        'timeout' => 15,
                        'headers' => array( 'Authorization' => 'Client-ID ' . $api_key ),
                    ) );

                    if ( is_wp_error( $response ) ) {
                        $lines[] = 'API request failed: ' . $response->get_error_message();
                    } else {
                        $http_code = wp_remote_retrieve_response_code( $response );
                        $body      = json_decode( wp_remote_retrieve_body( $response ), true );
                        $lines[]   = 'Unsplash HTTP status: ' . $http_code;

                        if ( $http_code !== 200 ) {
                            $lines[] = 'Error from Unsplash: ' . ( $body['errors'][0] ?? wp_remote_retrieve_body( $response ) );
                        } elseif ( empty( $body['urls']['regular'] ) ) {
                            $lines[] = 'No image URL in response. Keys returned: ' . implode( ', ', array_keys( $body ) );
                        } else {
                            $image_url = $body['urls']['regular'];
                            $lines[]   = 'Image URL: ' . $image_url;
                            $lines[]   = 'wp_http_validate_url result: ' . ( wp_http_validate_url( $image_url ) ? 'PASS' : 'FAIL' );
                            $lines[]   = 'esc_url_raw result: ' . esc_url_raw( $image_url );

                            $dl = wp_remote_get( $image_url, array( 'timeout' => 15 ) );
                            if ( is_wp_error( $dl ) ) {
                                $lines[] = 'Image download failed: ' . $dl->get_error_message();
                            } else {
                                $lines[] = 'Image download HTTP status: ' . wp_remote_retrieve_response_code( $dl );
                                $lines[] = 'Content-Type: ' . wp_remote_retrieve_header( $dl, 'content-type' );
                            }
                        }
                    }

                    echo '<div class="notice notice-info"><p><strong>Unsplash Diagnostic:</strong><br>' . nl2br( esc_html( implode( "\n", $lines ) ) ) . '</p></div>';
                }
            }
            ?>

            <hr />
            <h2>Scheduled Runs</h2>
            <?php
            $next_news  = wp_next_scheduled( RTN_Scheduler::NEWS_CRON_HOOK );
            $next_think = wp_next_scheduled( RTN_Scheduler::THINK_CRON_HOOK );
            echo '<p>News roundup (Mondays): <strong>' . ( $next_news  ? esc_html( get_date_from_gmt( date( 'Y-m-d H:i:s', $next_news ),  'F j, Y g:i a' ) ) : 'Not scheduled' ) . '</strong></p>';
            echo '<p>Think piece (Wednesdays): <strong>' . ( $next_think ? esc_html( get_date_from_gmt( date( 'Y-m-d H:i:s', $next_think ), 'F j, Y g:i a' ) ) : 'Not scheduled' ) . '</strong></p>';
            if ( ! $next_news || ! $next_think ) {
                echo '<p>Deactivate and reactivate the plugin to reschedule missing events.</p>';
            }
            ?>
        </div>
        <?php
    }
}
