<?php
/*
Plugin Name: Login Sentinel
Plugin URI: https://unifyr.io/login-sentinel
Description: Login Sentinel is a comprehensive security plugin for WordPress that logs all login attempts, detects brute-force attacks, and automatically blocks suspicious IP addresses. It records detailed geolocation (city and country) for every attempt and provides an intuitive dashboard with real-time metrics, interactive charts, and logs. Customizable settings allow you to adjust thresholds, block durations, log retention, and email notifications.
Version: 0.2
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
// Historical metrics and cleanup file removed.

if (is_admin()) {
  require_once LOGIN_SENTINEL_PLUGIN_DIR . 'admin/class-admin-menu.php';
  require_once LOGIN_SENTINEL_PLUGIN_DIR . 'admin/ajax/send-email-now.php';
  require_once LOGIN_SENTINEL_PLUGIN_DIR . 'admin/ajax/load-more-logs.php';
  require_once LOGIN_SENTINEL_PLUGIN_DIR . 'admin/ajax/get-historical-metrics.php';
  // Removed historical metrics ajax.
  require_once LOGIN_SENTINEL_PLUGIN_DIR . 'admin/ajax/settings-save.php';
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

  require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
  dbDelta($sql1);
  dbDelta($sql2);
}

// Retrieve plugin settings.
$settings = get_option('login_sentinel_settings', array());

// Disable XML-RPC if the option is enabled.
if (! empty($settings['disable_xmlrpc'])) {
  add_filter('xmlrpc_enabled', '__return_false');
}

// --- NEW: Update Expired IP Blocks Instead of Deleting Them ---
// Define a fixed cron schedule: every five minutes.
function login_sentinel_cron_schedules($schedules)
{
  $schedules['every_five_minutes'] = array(
    'interval' => 300, // 5 minutes = 300 seconds.
    'display'  => __('Every 5 Minutes', 'login-sentinel'),
  );
  return $schedules;
}
add_filter('cron_schedules', 'login_sentinel_cron_schedules');

// Schedule the update event if not already scheduled.
if (! wp_next_scheduled('login_sentinel_update_expired_ip_blocks_event')) {
  wp_schedule_event(time(), 'every_five_minutes', 'login_sentinel_update_expired_ip_blocks_event');
}
add_action('login_sentinel_update_expired_ip_blocks_event', 'login_sentinel_update_expired_ip_blocks');

/**
 * Update Expired IP Blocks:
 * Update any IP block record from "Blocked" to "Expired" if its blocked_time is older than block_duration minutes.
 */
function login_sentinel_update_expired_ip_blocks()
{
  global $wpdb;
  $settings = get_option('login_sentinel_settings', array('block_duration' => 60));
  $block_duration = intval($settings['block_duration']);
  $blocks_table = $wpdb->prefix . 'login_sentinel_ip_blocks';

  $wpdb->query(
    $wpdb->prepare(
      "UPDATE $blocks_table SET event = 'Expired' WHERE event = 'Blocked' AND blocked_time <= DATE_SUB(NOW(), INTERVAL %d MINUTE)",
      $block_duration
    )
  );
}

// Schedule a daily email alert cron event if not already scheduled.
if (! wp_next_scheduled('login_sentinel_daily_email_alert')) {
  wp_schedule_event(time(), 'daily', 'login_sentinel_daily_email_alert');
}
add_action('login_sentinel_daily_email_alert', 'login_sentinel_send_email_alerts_manual');

// Removed historical aggregation scheduling and cleanup (old cleanup routines are no longer used).

/* Example Logging Function */
function login_sentinel_log_attempt($user, $event, $ip, $location)
{
  global $wpdb;
  $table = $wpdb->prefix . 'login_sentinel_attempts';
  $wpdb->insert(
    $table,
    array(
      'time'            => current_time('mysql'),
      'user_identifier' => $user,
      'ip_address'      => $ip,
      'location'        => $location,
      'event'           => $event,
    ),
    array('%s', '%s', '%s', '%s', '%s')
  );
}

/* Example IP Block Function */
function login_sentinel_block_ip($ip)
{
  global $wpdb;
  $table = $wpdb->prefix . 'login_sentinel_ip_blocks';
  $wpdb->insert(
    $table,
    array(
      'ip_address'   => $ip,
      'blocked_time' => current_time('mysql'),
      'event'        => 'Blocked'
    ),
    array('%s', '%s', '%s')
  );
}

/* Additional Plugin Functionality */
// Example: Log successful login attempts.
function login_sentinel_record_login_attempt($user_login, $user)
{
  $ip = $_SERVER['REMOTE_ADDR'];
  $location = ''; // Optionally, integrate geolocation here.
  login_sentinel_log_attempt($user_login, 'Success', $ip, $location);
}
add_action('wp_login', 'login_sentinel_record_login_attempt', 10, 2);

// Additional hooks, AJAX handlers, scheduled tasks, etc. can be added here.
