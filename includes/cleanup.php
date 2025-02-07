<?php

/**
 * Cleanup Functions for Login Sentinel
 *
 * Performs cleanup of expired IP blocks and old login attempts based on plugin settings.
 *
 * @package Login_Sentinel
 */

if (! defined('ABSPATH')) {
  exit;
}

/**
 * Cleanup expired IP blocks and old login attempts.
 */
function login_sentinel_cleanup()
{
  global $wpdb;

  // Get settings.
  $settings = get_option('login_sentinel_settings', array(
    'block_duration' => 60, // in minutes.
    'log_retention'  => 30, // in days.
  ));
  $block_duration = intval($settings['block_duration']);
  $log_retention  = intval($settings['log_retention']);

  // Cleanup IP blocks: remove records where the block has expired.
  // An IP block is expired if blocked_time + block_duration minutes is less than or equal to NOW().
  $blocks_table = $wpdb->prefix . 'login_sentinel_ip_blocks';
  $deleted_blocks = $wpdb->query($wpdb->prepare(
    "DELETE FROM $blocks_table WHERE DATE_ADD(blocked_time, INTERVAL %d MINUTE) <= NOW()",
    $block_duration
  ));

  // Cleanup login attempts: remove records older than the log retention period.
  $attempts_table = $wpdb->prefix . 'login_sentinel_attempts';
  $deleted_attempts = $wpdb->query($wpdb->prepare(
    "DELETE FROM $attempts_table WHERE time < DATE_SUB(NOW(), INTERVAL %d DAY)",
    $log_retention
  ));

  error_log("[Login Sentinel] Cleanup: Deleted {$deleted_blocks} expired IP blocks and {$deleted_attempts} old login attempts.");
}
