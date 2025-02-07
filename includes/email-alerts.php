<?php

/**
 * Email Alerts for Login Sentinel
 *
 * Sends scheduled or manual email alerts with metrics based on the selected frequency.
 *
 * @package Login_Sentinel
 */

if (! defined('ABSPATH')) {
  exit;
}

// Include the email template file.
require_once LOGIN_SENTINEL_PLUGIN_DIR . 'includes/email-template.php';

/**
 * Prepares and sends an HTML email alert containing metrics.
 *
 * Supported frequencies:
 *   - daily: last 24 hours
 *   - weekly: last 7 days
 *   - monthly: last 30 days
 *
 * @param string $frequency Optional. Frequency to use; defaults to the setting value.
 * @return bool True if mail sent, false otherwise.
 */
function login_sentinel_send_email_alerts_manual($frequency = '')
{
  // Get plugin settings.
  $settings = get_option('login_sentinel_settings', array(
    'notification_email'    => '',
    'enable_notifications'  => 0,
    'email_frequency'       => 'daily',
  ));

  // If notifications are disabled or email is empty, return false.
  if (empty($settings['notification_email']) || ! $settings['enable_notifications']) {
    error_log('[Login Sentinel] Email alerts: Notifications disabled or email address empty.');
    return false;
  }

  // Determine frequency: use provided or fallback to setting.
  $freq = ! empty($frequency) ? $frequency : $settings['email_frequency'];
  if ('weekly' === $freq) {
    $start_time = date('Y-m-d H:i:s', strtotime('-7 days'));
    $subject    = __('Weekly Login Sentinel Metrics', 'login-sentinel');
  } elseif ('monthly' === $freq) {
    $start_time = date('Y-m-d H:i:s', strtotime('-30 days'));
    $subject    = __('Monthly Login Sentinel Metrics', 'login-sentinel');
  } else {
    // Default to daily.
    $start_time = date('Y-m-d H:i:s', strtotime('-24 hours'));
    $subject    = __('Daily Login Sentinel Metrics', 'login-sentinel');
  }

  global $wpdb;
  $attempts_table = $wpdb->prefix . 'login_sentinel_attempts';
  $blocks_table   = $wpdb->prefix . 'login_sentinel_ip_blocks';

  $success = intval($wpdb->get_var($wpdb->prepare(
    "SELECT COUNT(*) FROM $attempts_table WHERE event = %s AND time >= %s",
    'Success',
    $start_time
  )));
  $failed  = intval($wpdb->get_var($wpdb->prepare(
    "SELECT COUNT(*) FROM $attempts_table WHERE event = %s AND time >= %s",
    'Failed',
    $start_time
  )));
  $blocked = intval($wpdb->get_var($wpdb->prepare(
    "SELECT COUNT(*) FROM $attempts_table WHERE event = %s AND time >= %s",
    'Blocked',
    $start_time
  )));
  $total   = $success + $failed;
  $active  = intval($wpdb->get_var($wpdb->prepare(
    "SELECT COUNT(*) FROM $blocks_table WHERE blocked_time >= %s",
    $start_time
  )));

  $metrics = array(
    'success' => $success,
    'failed'  => $failed,
    'blocked' => $blocked,
    'total_login_attempts'   => $total,
    'active'  => $active,
  );

  // Use the separate email template.
  $body = login_sentinel_get_email_template($subject, $start_time, $metrics);

  $headers = array(
    'Content-Type: text/html; charset=UTF-8'
  );

  $result = wp_mail($settings['notification_email'], $subject, $body, $headers);
  if (! $result) {
    error_log('[Login Sentinel] wp_mail failed for subject: ' . $subject);
  }
  return $result;
}
