<?php

/**
 * AJAX Handler: Get Historical Metrics
 *
 * Calculates aggregated metrics and chart data for a given date range
 * from the login attempts and IP blocks tables.
 *
 * Expects POST parameters:
 *   - start_date (YYYY-MM-DD)
 *   - end_date (YYYY-MM-DD)
 *   - nonce (for security)
 *
 * Returns a JSON response with:
 *   - aggregated metrics: total_success, total_failed, total_blocked, total_login_attempts, total_ip_blocks
 *   - chart data: labels and datasets for Successful Logins, Failed Logins, Blocked Logins, Total Login Attempts, Active IP Blocks.
 *   - (optional) no_data flag if no metrics are available.
 *
 * @package Login_Sentinel
 */

if (! defined('ABSPATH')) {
  exit;
}

function login_sentinel_get_historical_metrics_callback()
{
  // Verify nonce.
  if (! isset($_POST['nonce']) || ! wp_verify_nonce($_POST['nonce'], 'login_sentinel_get_historical_metrics')) {
    wp_send_json_error(array('message' => __('Security check failed', 'login-sentinel')));
  }

  // Retrieve start_date and end_date.
  $start_date = isset($_POST['start_date']) ? sanitize_text_field($_POST['start_date']) : '';
  $end_date   = isset($_POST['end_date']) ? sanitize_text_field($_POST['end_date']) : '';

  if (empty($start_date) || empty($end_date)) {
    wp_send_json_error(array('message' => __('Invalid date range', 'login-sentinel')));
  }

  // Normalize dates to YYYY-MM-DD.
  $start_date = date('Y-m-d', strtotime($start_date));
  $end_date   = date('Y-m-d', strtotime($end_date));

  // Build date range array.
  $date_range = array();
  $current = strtotime($start_date);
  $end = strtotime($end_date);
  while ($current <= $end) {
    $date_range[] = date('Y-m-d', $current);
    $current = strtotime('+1 day', $current);
  }

  global $wpdb;
  $attempts_table = $wpdb->prefix . 'login_sentinel_attempts';
  $blocks_table   = $wpdb->prefix . 'login_sentinel_ip_blocks';

  // Initialize arrays for chart data.
  $labels = array();
  $success_data = array();
  $failed_data = array();
  $blocked_data = array();
  $total_data = array();
  $ip_blocks_data = array();

  // Initialize aggregated counters.
  $total_success = 0;
  $total_failed = 0;
  $total_blocked = 0;
  $total_login_attempts = 0;
  $total_ip_blocks = 0;

  foreach ($date_range as $date) {
    $labels[] = date('M j', strtotime($date));

    // Query login attempts counts for this day.
    $success = intval($wpdb->get_var($wpdb->prepare(
      "SELECT COUNT(*) FROM $attempts_table WHERE event = %s AND DATE(time) = %s",
      'Success',
      $date
    )));
    $failed = intval($wpdb->get_var($wpdb->prepare(
      "SELECT COUNT(*) FROM $attempts_table WHERE event = %s AND DATE(time) = %s",
      'Failed',
      $date
    )));
    $blocked = intval($wpdb->get_var($wpdb->prepare(
      "SELECT COUNT(*) FROM $attempts_table WHERE event = %s AND DATE(time) = %s",
      'Blocked',
      $date
    )));
    $total_attempts = $success + $failed;

    // Query IP blocks for this day.
    $ip_blocks = intval($wpdb->get_var($wpdb->prepare(
      "SELECT COUNT(*) FROM $blocks_table WHERE DATE(blocked_time) = %s",
      $date
    )));

    $success_data[] = $success;
    $failed_data[] = $failed;
    $blocked_data[] = $blocked;
    $total_data[] = $total_attempts;
    $ip_blocks_data[] = $ip_blocks;

    $total_success += $success;
    $total_failed  += $failed;
    $total_blocked += $blocked;
    $total_login_attempts += $total_attempts;
    $total_ip_blocks += $ip_blocks;
  }

  // Determine if there is no data.
  $no_data = ($total_success === 0 && $total_failed === 0 && $total_blocked === 0 && $total_login_attempts === 0 && $total_ip_blocks === 0);

  $response_data = array(
    'aggregated' => array(
      'total_success' => $total_success,
      'total_failed'  => $total_failed,
      'total_blocked' => $total_blocked,
      'total_login_attempts' => $total_login_attempts,
      'total_ip_blocks' => $total_ip_blocks,
    ),
    'chart' => array(
      'labels' => $labels,
      'datasets' => array(
        array(
          'label' => __('Successful Logins', 'login-sentinel'),
          'data' => $success_data,
          'backgroundColor' => 'rgba(16, 185, 129, 0.2)',
          'borderColor' => 'rgba(16, 185, 129, 1)',
          'borderWidth' => 2,
          'fill' => true,
          'tension' => 0.1,
        ),
        array(
          'label' => __('Failed Logins', 'login-sentinel'),
          'data' => $failed_data,
          'backgroundColor' => 'rgba(239, 68, 68, 0.2)',
          'borderColor' => 'rgba(239, 68, 68, 1)',
          'borderWidth' => 2,
          'fill' => true,
          'tension' => 0.1,
        ),
        array(
          'label' => __('Blocked Logins', 'login-sentinel'),
          'data' => $blocked_data,
          'backgroundColor' => 'rgba(234, 179, 8, 0.2)',
          'borderColor' => 'rgba(234, 179, 8, 1)',
          'borderWidth' => 2,
          'fill' => true,
          'tension' => 0.1,
        ),
        array(
          'label' => __('Total Login Attempts', 'login-sentinel'),
          'data' => $total_data,
          'backgroundColor' => 'rgba(59, 130, 246, 0.2)',
          'borderColor' => 'rgba(59, 130, 246, 1)',
          'borderWidth' => 2,
          'fill' => true,
          'tension' => 0.1,
        ),
        array(
          'label' => __('Active IP Blocks', 'login-sentinel'),
          'data' => $ip_blocks_data,
          'backgroundColor' => 'rgba(139, 92, 246, 0.2)',
          'borderColor' => 'rgba(139, 92, 246, 1)',
          'borderWidth' => 2,
          'fill' => true,
          'tension' => 0.1,
        ),
      ),
    ),
    'no_data' => $no_data,
  );

  wp_send_json_success($response_data);
}
add_action('wp_ajax_login_sentinel_get_historical_metrics', 'login_sentinel_get_historical_metrics_callback');
