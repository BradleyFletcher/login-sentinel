<?php

/**
 * Login Sentinel Login Feedback
 *
 * Provides custom feedback on the login screen, informing users how many attempts they have left
 * or, if blocked, the cooldown time remaining.
 *
 * @package Login_Sentinel
 */

if (! defined('ABSPATH')) {
  exit;
}

/**
 * Adds custom login error messages to the login screen.
 *
 * Translators: %d is the number of login attempts left or the number of minutes remaining.
 *
 * @param string $errors Existing login error messages.
 * @return string Modified login error messages.
 */
function login_sentinel_custom_login_errors($errors)
{
  global $wpdb;

  // Get plugin settings with default values.
  $settings = get_option('login_sentinel_settings', array(
    'failed_attempts_threshold' => 5,
    'time_window'               => 15, // in minutes
  ));
  $threshold   = intval($settings['failed_attempts_threshold']);
  $time_window = intval($settings['time_window']);

  // Use gmdate() for consistency (adjust if you prefer local time).
  $time_window_start = gmdate('Y-m-d H:i:s', strtotime("-{$time_window} minutes"));

  // Get the user's IP address.
  $ip = isset($_SERVER['REMOTE_ADDR']) ? wp_unslash($_SERVER['REMOTE_ADDR']) : '';
  if (empty($ip)) {
    return $errors;
  }

  // Count the number of failed login attempts from this IP within the time window.
  $attempts_table = $wpdb->prefix . 'login_sentinel_attempts';
  $failed_attempts = intval($wpdb->get_var($wpdb->prepare(
    "SELECT COUNT(*) FROM $attempts_table WHERE ip_address = %s AND event = %s AND time >= %s",
    $ip,
    'Failed',
    $time_window_start
  )));

  if ($failed_attempts < $threshold) {
    $attempts_left = $threshold - $failed_attempts;
    /* Translators: %d is the number of login attempts left. */
    $feedback = sprintf(esc_html__('You have %d login attempt(s) left before your IP is blocked.', 'login-sentinel'), $attempts_left);
    $errors .= '<br/><strong>' . $feedback . '</strong>';
  } else {
    // If the threshold is reached, determine the cooldown time.
    $oldest_time = $wpdb->get_var($wpdb->prepare(
      "SELECT MIN(time) FROM $attempts_table WHERE ip_address = %s AND event = %s AND time >= %s",
      $ip,
      'Failed',
      $time_window_start
    ));
    if ($oldest_time) {
      $time_diff = strtotime($oldest_time) + ($time_window * 60) - time();
      if ($time_diff < 0) {
        $time_diff = 0;
      }
      $minutes = ceil($time_diff / 60);
      /* Translators: %d is the number of minutes remaining. */
      $feedback = sprintf(esc_html__('Too many failed attempts. Please wait %d minute(s) before trying again.', 'login-sentinel'), $minutes);
      $errors .= '<br/><strong>' . $feedback . '</strong>';
    }
  }

  return $errors;
}
add_filter('login_errors', 'login_sentinel_custom_login_errors');
