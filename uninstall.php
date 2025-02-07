<?php
if (! defined('WP_UNINSTALL_PLUGIN')) {
  exit;
}

global $wpdb;
$attempts_table = $wpdb->prefix . 'login_sentinel_attempts';
$blocks_table   = $wpdb->prefix . 'login_sentinel_ip_blocks';

$wpdb->query("DROP TABLE IF EXISTS $attempts_table");
$wpdb->query("DROP TABLE IF EXISTS $blocks_table");

// Remove plugin settings.
delete_option('login_sentinel_settings');
