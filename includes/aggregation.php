<?php

/**
 * Aggregation Functions for Login Sentinel
 *
 * Aggregates daily metrics from raw logs into a historical metrics table.
 *
 * @package Login_Sentinel
 */

if (! defined('ABSPATH')) {
  exit;
}

/**
 * Aggregates daily metrics for the previous day.
 *
 * This function calculates the number of successful, failed, blocked,
 * and total login attempts as well as the number of IP blocks triggered
 * on a given day, and then inserts (or updates) a summary record in the
 * historical metrics table.
 *
 * @return mixed The number of affected rows, or false on error.
 */
function login_sentinel_aggregate_daily_metrics()
{
  global $wpdb;

  // Table names.
  $attempts_table  = $wpdb->prefix . 'login_sentinel_attempts';
  $blocks_table    = $wpdb->prefix . 'login_sentinel_ip_blocks';
  $historical_table = $wpdb->prefix . 'login_sentinel_historical_metrics';

  // Determine the date for aggregation: yesterday's date.
  $date = date('Y-m-d', strtotime('-1 day'));

  // Aggregate metrics from login attempts table for that date.
  $success = intval($wpdb->get_var($wpdb->prepare(
    "SELECT COUNT(*) FROM $attempts_table WHERE event = %s AND DATE(time) = %s",
    'Success',
    $date
  )));
  $failed  = intval($wpdb->get_var($wpdb->prepare(
    "SELECT COUNT(*) FROM $attempts_table WHERE event = %s AND DATE(time) = %s",
    'Failed',
    $date
  )));
  $blocked = intval($wpdb->get_var($wpdb->prepare(
    "SELECT COUNT(*) FROM $attempts_table WHERE event = %s AND DATE(time) = %s",
    'Blocked',
    $date
  )));
  $total   = $success + $failed;

  // For IP blocks, count how many were triggered on that day.
  $blocks_count = intval($wpdb->get_var($wpdb->prepare(
    "SELECT COUNT(*) FROM $blocks_table WHERE DATE(blocked_time) = %s",
    $date
  )));

  // Insert or update aggregated data into the historical table.
  $result = $wpdb->query(
    $wpdb->prepare(
      "INSERT INTO $historical_table (date, successful_logins, failed_logins, blocked_logins, total_logins, ip_blocks_triggered)
			VALUES (%s, %d, %d, %d, %d, %d)
			ON DUPLICATE KEY UPDATE
				successful_logins = VALUES(successful_logins),
				failed_logins = VALUES(failed_logins),
				blocked_logins = VALUES(blocked_logins),
				total_logins = VALUES(total_logins),
				ip_blocks_triggered = VALUES(ip_blocks_triggered)",
      $date,
      $success,
      $failed,
      $blocked,
      $total,
      $blocks_count
    )
  );

  error_log("[Login Sentinel] Aggregated metrics for {$date}: Success: {$success}, Failed: {$failed}, Blocked: {$blocked}, Total: {$total}, IP Blocks: {$blocks_count}");
  return $result;
}
