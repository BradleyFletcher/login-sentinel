<?php

/**
 * AJAX Handler: Save Login Sentinel Settings
 *
 * This file handles the Ajax request to save the plugin settings.
 *
 * @package Login_Sentinel
 */

if (! defined('ABSPATH')) {
  exit;
}

function login_sentinel_save_settings_callback()
{
  // Verify the nonce using the field "login_sentinel_settings_nonce" and action "save_login_sentinel_settings".
  if (! isset($_POST['login_sentinel_settings_nonce']) || ! wp_verify_nonce($_POST['login_sentinel_settings_nonce'], 'save_login_sentinel_settings')) {
    wp_send_json_error(array('message' => __('Security check failed', 'login-sentinel')));
  }

  // Retrieve and sanitize POST data.
  $failed_attempts_threshold = isset($_POST['failed_attempts_threshold']) ? intval($_POST['failed_attempts_threshold']) : 5;
  $time_window               = isset($_POST['time_window']) ? intval($_POST['time_window']) : 15;
  $block_duration            = isset($_POST['block_duration']) ? intval($_POST['block_duration']) : 60;
  $log_retention             = isset($_POST['log_retention']) ? intval($_POST['log_retention']) : 30;
  $notification_email        = isset($_POST['notification_email']) ? sanitize_email($_POST['notification_email']) : '';
  $enable_notifications      = isset($_POST['enable_notifications']) ? 1 : 0;
  $email_frequency           = isset($_POST['email_frequency']) ? sanitize_text_field($_POST['email_frequency']) : 'daily';
  // The Disable XML-RPC checkbox is nested under login_sentinel_settings.
  $disable_xmlrpc            = isset($_POST['login_sentinel_settings']['disable_xmlrpc']) ? 1 : 0;
  // New (optional): Cleanup Frequency field.
  $cleanup_frequency         = isset($_POST['cleanup_frequency']) ? sanitize_text_field($_POST['cleanup_frequency']) : 'daily';

  // Build the settings array.
  $settings = array(
    'failed_attempts_threshold' => $failed_attempts_threshold,
    'time_window'               => $time_window,
    'block_duration'            => $block_duration,
    'log_retention'             => $log_retention,
    'notification_email'        => $notification_email,
    'enable_notifications'      => $enable_notifications,
    'email_frequency'           => $email_frequency,
    'disable_xmlrpc'            => $disable_xmlrpc,
    'cleanup_frequency'         => $cleanup_frequency,
  );

  // Update the settings in the database.
  update_option('login_sentinel_settings', $settings);

  // Return a JSON success response.
  wp_send_json_success(array('message' => __('Settings saved successfully.', 'login-sentinel')));
}
add_action('wp_ajax_login_sentinel_save_settings', 'login_sentinel_save_settings_callback');
