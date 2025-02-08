<?php

/**
 * Email Alerts for Login Sentinel
 *
 * Sends email alerts with aggregated metrics from login attempts and IP blocks,
 * using a time range based on the email frequency selected in the settings.
 *
 * @package Login_Sentinel
 */

if (! defined('ABSPATH')) {
  exit;
}

/**
 * Sends an email alert with metrics covering the period determined by the email frequency setting.
 */
function login_sentinel_send_email_alerts()
{
  // Retrieve settings.
  $settings = get_option('login_sentinel_settings', array(
    'log_retention'       => 30,
    'notification_email'  => '',
    'enable_notifications' => 0,
    'block_duration'      => 60,
    'email_frequency'     => 'daily',
  ));

  // Only proceed if email notifications are enabled and a notification email is provided.
  if (empty($settings['enable_notifications']) || empty($settings['notification_email'])) {
    return;
  }

  // Determine the period to cover based on the email frequency.
  $frequency = $settings['email_frequency'];
  switch ($frequency) {
    case 'weekly':
      $period = '-7 days';
      break;
    case 'monthly':
      $period = '-30 days';
      break;
    case 'daily':
    default:
      $period = '-24 hours';
      break;
  }
  $start_time = date('Y-m-d H:i:s', strtotime($period));

  global $wpdb;
  $attempts_table = $wpdb->prefix . 'login_sentinel_attempts';
  $blocks_table   = $wpdb->prefix . 'login_sentinel_ip_blocks';

  // Calculate aggregated metrics for login attempts over the selected period.
  $agg_success = intval($wpdb->get_var($wpdb->prepare(
    "SELECT COUNT(*) FROM $attempts_table WHERE event = %s AND time >= %s",
    'Success',
    $start_time
  )));
  $agg_failed  = intval($wpdb->get_var($wpdb->prepare(
    "SELECT COUNT(*) FROM $attempts_table WHERE event = %s AND time >= %s",
    'Failed',
    $start_time
  )));
  $agg_blocked = intval($wpdb->get_var($wpdb->prepare(
    "SELECT COUNT(*) FROM $attempts_table WHERE event = %s AND time >= %s",
    'Blocked',
    $start_time
  )));
  $agg_total   = $agg_success + $agg_failed;

  // Calculate the number of IP blocks recorded over the selected period.
  $agg_ipblocks = intval($wpdb->get_var($wpdb->prepare(
    "SELECT COUNT(*) FROM $blocks_table WHERE blocked_time >= %s",
    $start_time
  )));

  // Build the email content.
  $subject = __('Login Sentinel Metrics Alert', 'login-sentinel');
  $message = "<h1>" . __('Login Sentinel Metrics', 'login-sentinel') . "</h1>";
  $message .= "<p><strong>" . __('Period Covered', 'login-sentinel') . ":</strong> " . date('M j, Y, g:i a', strtotime($start_time)) . " to " . current_time('mysql') . "</p>";
  $message .= "<p><strong>" . __('Successful Logins', 'login-sentinel') . ":</strong> " . number_format($agg_success) . "</p>";
  $message .= "<p><strong>" . __('Failed Logins', 'login-sentinel') . ":</strong> " . number_format($agg_failed) . "</p>";
  $message .= "<p><strong>" . __('Blocked Logins', 'login-sentinel') . ":</strong> " . number_format($agg_blocked) . "</p>";
  $message .= "<p><strong>" . __('Total Login Attempts', 'login-sentinel') . ":</strong> " . number_format($agg_total) . "</p>";
  $message .= "<p><strong>" . __('IP Blocks Recorded', 'login-sentinel') . ":</strong> " . number_format($agg_ipblocks) . "</p>";

  // Log the metrics for debugging.
  error_log("[Login Sentinel Email] Metrics for period starting {$start_time}: Success: {$agg_success}, Failed: {$agg_failed}, Blocked: {$agg_blocked}, Total Attempts: {$agg_total}, IP Blocks: {$agg_ipblocks}");

  // Set headers for HTML email.
  $headers = array('Content-Type: text/html; charset=UTF-8');

  // Send the email.
  wp_mail($settings['notification_email'], $subject, $message, $headers);
}

// Hook the function to the daily email alert event.
add_action('login_sentinel_daily_email_alert', 'login_sentinel_send_email_alerts');
