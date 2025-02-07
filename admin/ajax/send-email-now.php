<?php

/**
 * Send Metrics Email Now AJAX Handler
 *
 * Sends an email with metrics for the selected timeframe upon request.
 *
 * @package Login_Sentinel
 */

if (! defined('ABSPATH')) {
  exit;
}

add_action('wp_ajax_login_sentinel_send_email_now', 'login_sentinel_send_email_now_callback');
function login_sentinel_send_email_now_callback()
{
  // Check that nonce is provided.
  if (empty($_POST['nonce'])) {
    error_log('[Login Sentinel] send-email-now: Nonce missing in AJAX request.');
    wp_send_json_error('Nonce missing.');
    die();
  }

  // Verify the nonce.
  if (! wp_verify_nonce($_POST['nonce'], 'login_sentinel_send_email_now_nonce')) {
    error_log('[Login Sentinel] send-email-now: Invalid nonce. Received: ' . print_r($_POST['nonce'], true));
    wp_send_json_error('Invalid nonce.');
    die();
  }

  // Get frequency from POST.
  $frequency = isset($_POST['frequency']) ? sanitize_text_field(wp_unslash($_POST['frequency'])) : 'daily';

  $result = login_sentinel_send_email_alerts_manual($frequency);
  if ($result) {
    wp_send_json_success('Email sent successfully.');
  } else {
    error_log('[Login Sentinel] send-email-now: Email function failed for frequency: ' . $frequency);
    wp_send_json_error('Failed to send email.');
  }
  die();
}
