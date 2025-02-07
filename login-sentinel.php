<?php
/*
Plugin Name: Login Sentinel
Plugin URI: https://unifyr.io/login-sentinel
Description: Login Sentinel is a comprehensive security plugin for WordPress that logs all login attempts, detects brute-force attacks, and automatically blocks suspicious IP addresses. It records detailed geolocation (city and country) for every attempt and provides an intuitive dashboard with real-time metrics, interactive charts, and logs. Customizable settings allow you to adjust thresholds, block durations, log retention, and email notifications.
Version: 0.1
Author: Brad Fletcher
Author URI: https://example.com
Donate link: https://unifyr.io/donate
License: GPL2
Text Domain: login-sentinel
*/

if (! defined('ABSPATH')) {
  exit;
}

// Define plugin constants.
define('LOGIN_SENTINEL_PLUGIN_DIR', plugin_dir_path(__FILE__));
define('LOGIN_SENTINEL_PLUGIN_URL', plugin_dir_url(__FILE__));

// Include required files.
require_once LOGIN_SENTINEL_PLUGIN_DIR . 'includes/functions.php';
require_once LOGIN_SENTINEL_PLUGIN_DIR . 'includes/class-logger.php';
require_once LOGIN_SENTINEL_PLUGIN_DIR . 'includes/class-firewall.php';
require_once LOGIN_SENTINEL_PLUGIN_DIR . 'includes/class-ip-block.php';
require_once LOGIN_SENTINEL_PLUGIN_DIR . 'includes/class-settings.php';
require_once LOGIN_SENTINEL_PLUGIN_DIR . 'includes/login-feedback.php';
require_once LOGIN_SENTINEL_PLUGIN_DIR . 'includes/email-alerts.php';
require_once LOGIN_SENTINEL_PLUGIN_DIR . 'includes/aggregation.php';
require_once LOGIN_SENTINEL_PLUGIN_DIR . 'includes/cleanup.php';

if (is_admin()) {
  require_once LOGIN_SENTINEL_PLUGIN_DIR . 'admin/class-admin-menu.php';
  require_once LOGIN_SENTINEL_PLUGIN_DIR . 'admin/ajax/send-email-now.php';
  require_once LOGIN_SENTINEL_PLUGIN_DIR . 'admin/ajax/load-more-logs.php';
  require_once LOGIN_SENTINEL_PLUGIN_DIR . 'admin/ajax/get-historical-metrics.php';
} else {
  require_once LOGIN_SENTINEL_PLUGIN_DIR . 'public/class-shortcodes.php';
}

// Activation hook: create necessary database tables.
register_activation_hook(__FILE__, 'login_sentinel_activate');
function login_sentinel_activate()
{
  global $wpdb;
  $charset_collate = $wpdb->get_charset_collate();

  // Table for login attempts.
  $attempts_table = $wpdb->prefix . 'login_sentinel_attempts';
  $sql1 = "CREATE TABLE $attempts_table (
        id BIGINT(20) UNSIGNED NOT NULL AUTO_INCREMENT,
        time DATETIME NOT NULL,
        ip_address VARCHAR(100) NOT NULL,
        user_identifier VARCHAR(100) NOT NULL,
        location VARCHAR(100) DEFAULT '',
        event VARCHAR(50) NOT NULL,
        PRIMARY KEY  (id)
    ) $charset_collate;";

  // Table for IP blocks.
  $blocks_table = $wpdb->prefix . 'login_sentinel_ip_blocks';
  $sql2 = "CREATE TABLE $blocks_table (
        id BIGINT(20) UNSIGNED NOT NULL AUTO_INCREMENT,
        ip_address VARCHAR(100) NOT NULL,
        location VARCHAR(100) DEFAULT '',
        blocked_time DATETIME NOT NULL,
        event VARCHAR(50) NOT NULL,
        PRIMARY KEY  (id)
    ) $charset_collate;";

  // Table for historical metrics.
  $historical_table = $wpdb->prefix . 'login_sentinel_historical_metrics';
  $sql3 = "CREATE TABLE $historical_table (
        date DATE NOT NULL,
        successful_logins INT NOT NULL,
        failed_logins INT NOT NULL,
        blocked_logins INT NOT NULL,
        total_logins INT NOT NULL,
        ip_blocks_triggered INT NOT NULL,
        PRIMARY KEY  (date)
    ) $charset_collate;";

  require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
  dbDelta($sql1);
  dbDelta($sql2);
  dbDelta($sql3);
}

// Disable XML-RPC if the option is enabled.
if (! empty($settings['disable_xmlrpc'])) {
  add_filter('xmlrpc_enabled', '__return_false');
}

// Schedule a daily email alert cron event if not already scheduled.
if (! wp_next_scheduled('login_sentinel_daily_email_alert')) {
  wp_schedule_event(time(), 'daily', 'login_sentinel_daily_email_alert');
}
add_action('login_sentinel_daily_email_alert', 'login_sentinel_send_email_alerts_manual');

// Schedule a daily cleanup event.
if (! wp_next_scheduled('login_sentinel_cleanup_event')) {
  wp_schedule_event(time(), 'daily', 'login_sentinel_cleanup_event');
}
add_action('login_sentinel_cleanup_event', 'login_sentinel_cleanup');

// Schedule a daily aggregation event.
if (! wp_next_scheduled('login_sentinel_aggregation_event')) {
  wp_schedule_event(time(), 'daily', 'login_sentinel_aggregation_event');
}
add_action('login_sentinel_aggregation_event', 'login_sentinel_aggregate_daily_metrics');
