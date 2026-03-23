<?php
/**
 * Plugin Name: RobotsThink AI News
 * Plugin URI:  https://github.com/rasdo11/robotsthink
 * Description: Automatically fetches AI news and publishes a think piece every two days using Claude AI.
 * Version:     1.0.0
 * Author:      RobotsThink
 * License:     MIT
 */

if ( ! defined( 'ABSPATH' ) ) exit;

define( 'RTN_PLUGIN_DIR', plugin_dir_path( __FILE__ ) );
define( 'RTN_VERSION', '1.1.0' );

require_once RTN_PLUGIN_DIR . 'includes/class-news-fetcher.php';
require_once RTN_PLUGIN_DIR . 'includes/class-think-piece-generator.php';
require_once RTN_PLUGIN_DIR . 'includes/class-photo-fetcher.php';
require_once RTN_PLUGIN_DIR . 'includes/class-post-publisher.php';
require_once RTN_PLUGIN_DIR . 'includes/class-scheduler.php';
require_once RTN_PLUGIN_DIR . 'includes/class-settings.php';

// Boot the plugin
RTN_Settings::init();
RTN_Scheduler::init();

register_activation_hook( __FILE__, array( 'RTN_Scheduler', 'activate' ) );
register_deactivation_hook( __FILE__, array( 'RTN_Scheduler', 'deactivate' ) );
