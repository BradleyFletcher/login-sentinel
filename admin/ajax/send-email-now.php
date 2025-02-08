<?php

/**
 * AJAX Handler: Send Metrics Email Now
 *
 * Processes the AJAX request to send the metrics email immediately.
 * Uses the email template defined in includes/email-template.php.
 *
 * Expects POST parameters:
 *   - frequency: (daily, weekly, monthly)
 *   - nonce: security nonce (login_sentinel_send_email_now_nonce)
 *
 * @package Login_Sentinel
 */

if (! defined('ABSPATH')) {
  exit;
}

function login_sentinel_send_email_now_callback()
{
  // Verify the nonce.
  if (! isset($_POST['nonce']) || ! wp_verify_nonce($_POST['nonce'], 'login_sentinel_send_email_now_nonce')) {
    wp_send_json_error(array('message' => __('Security check failed', 'login-sentinel')));
  }

  // Get the frequency parameter.
  $frequency = isset($_POST['frequency']) ? sanitize_text_field($_POST['frequency']) : 'daily';

  // Determine the period based on frequency.
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
  $current_time = current_time('mysql');

  global $wpdb;
  $attempts_table = $wpdb->prefix . 'login_sentinel_attempts';
  $blocks_table   = $wpdb->prefix . 'login_sentinel_ip_blocks';

  // Calculate metrics.
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
  $agg_ipblocks = intval($wpdb->get_var($wpdb->prepare(
    "SELECT COUNT(*) FROM $blocks_table WHERE blocked_time >= %s",
    $start_time
  )));

  // Build the metrics array expected by the email template.
  $metrics = array(
    'success' => $agg_success,
    'failed'  => $agg_failed,
    'blocked' => $agg_blocked,
    'total_login_attempts' => $agg_total,
    'active'  => $agg_ipblocks,
  );

  // Retrieve the recipient email from settings.
  $settings = get_option('login_sentinel_settings', array());
  $recipient = isset($settings['notification_email']) ? $settings['notification_email'] : '';
  if (empty($recipient)) {
    wp_send_json_error(array('message' => __('No notification email is set in the settings.', 'login-sentinel')));
  }

  // Define the email subject.
  $email_subject = __('Immediate Login Sentinel Metrics', 'login-sentinel');

  // Use the email template function to generate the email content.
  if (! function_exists('login_sentinel_get_email_template')) {
    // If the template function is not loaded, try including the file.
    require_once LOGIN_SENTINEL_PLUGIN_DIR . 'includes/email-template.php';
  }
  $message = login_sentinel_get_email_template($email_subject, $start_time, $metrics);

  // Set headers for HTML email.
  $headers = array('Content-Type: text/html; charset=UTF-8');

  // Debug log the metrics.
  error_log("[Login Sentinel Email] Metrics for period starting {$start_time}: Success: {$agg_success}, Failed: {$agg_failed}, Blocked: {$agg_blocked}, Total: {$agg_total}, IP Blocks: {$agg_ipblocks}");

  // Send the email.
  if (wp_mail($recipient, $email_subject, $message, $headers)) {
    wp_send_json_success(array('message' => __('Email sent successfully.', 'login-sentinel')));
  } else {
    wp_send_json_error(array('message' => __('Failed to send email.', 'login-sentinel')));
  }
}
add_action('wp_ajax_login_sentinel_send_email_now', 'login_sentinel_send_email_now_callback');
