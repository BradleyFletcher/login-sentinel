<?php

/**
 * Get Historical Metrics AJAX Handler
 *
 * Returns aggregated historical metrics and daily chart data for a specified date range.
 *
 * @package Login_Sentinel
 */

if (! defined('ABSPATH')) {
  exit;
}

add_action('wp_ajax_login_sentinel_get_historical_metrics', 'login_sentinel_get_historical_metrics_callback');
function login_sentinel_get_historical_metrics_callback()
{
  // Check that nonce is provided.
  if (empty($_POST['nonce'])) {
    wp_send_json_error('Nonce missing.');
    wp_die();
  }
  // Verify the nonce.
  if (! wp_verify_nonce($_POST['nonce'], 'login_sentinel_get_historical_metrics_nonce')) {
    wp_send_json_error('Invalid nonce.');
    wp_die();
  }

  // Get start and end dates.
  $start_date = isset($_POST['start_date']) ? sanitize_text_field(wp_unslash($_POST['start_date'])) : '';
  $end_date   = isset($_POST['end_date'])   ? sanitize_text_field(wp_unslash($_POST['end_date']))   : '';

  if (empty($start_date) || empty($end_date)) {
    wp_send_json_error('Start date and end date are required.');
    wp_die();
  }

  global $wpdb;
  $historical_table = $wpdb->prefix . 'login_sentinel_historical_metrics';

  // Query aggregated metrics for the date range.
  $row = $wpdb->get_row($wpdb->prepare(
    "SELECT 
			SUM(successful_logins) AS total_success,
			SUM(failed_logins) AS total_failed,
			SUM(blocked_logins) AS total_blocked,
			SUM(total_logins) AS total_login_attempts,
			SUM(ip_blocks_triggered) AS total_ip_blocks
		 FROM $historical_table
		 WHERE date BETWEEN %s AND %s",
    $start_date,
    $end_date
  ), ARRAY_A);

  // Query daily data for the chart.
  $daily = $wpdb->get_results($wpdb->prepare(
    "SELECT date, successful_logins, failed_logins, blocked_logins, total_logins, ip_blocks_triggered
		 FROM $historical_table
		 WHERE date BETWEEN %s AND %s
		 ORDER BY date ASC",
    $start_date,
    $end_date
  ), ARRAY_A);

  $labels = array();
  $successData = array();
  $failedData = array();
  $blockedData = array();
  $totalData = array();
  $ipBlocksData = array();

  if ($daily) {
    foreach ($daily as $d) {
      $labels[] = date('M j', strtotime($d['date']));
      $successData[] = intval($d['successful_logins']);
      $failedData[] = intval($d['failed_logins']);
      $blockedData[] = intval($d['blocked_logins']);
      $totalData[] = intval($d['total_logins']);
      $ipBlocksData[] = intval($d['ip_blocks_triggered']);
    }
  }

  $response = array(
    'aggregated' => $row,
    'chart' => array(
      'labels' => $labels,
      'datasets' => array(
        array(
          'label' => 'Successful Logins',
          'data' => $successData,
          'backgroundColor' => 'rgba(16, 185, 129, 0.2)',
          'borderColor' => 'rgba(16, 185, 129, 1)',
          'borderWidth' => 2,
          'fill' => true,
          'tension' => 0.1
        ),
        array(
          'label' => 'Failed Logins',
          'data' => $failedData,
          'backgroundColor' => 'rgba(239, 68, 68, 0.2)',
          'borderColor' => 'rgba(239, 68, 68, 1)',
          'borderWidth' => 2,
          'fill' => true,
          'tension' => 0.1
        ),
        array(
          'label' => 'Blocked Logins',
          'data' => $blockedData,
          'backgroundColor' => 'rgba(234, 179, 8, 0.2)',
          'borderColor' => 'rgba(234, 179, 8, 1)',
          'borderWidth' => 2,
          'fill' => true,
          'tension' => 0.1
        ),
        array(
          'label' => 'Total Login Attempts',
          'data' => $totalData,
          'backgroundColor' => 'rgba(59, 130, 246, 0.2)',
          'borderColor' => 'rgba(59, 130, 246, 1)',
          'borderWidth' => 2,
          'fill' => true,
          'tension' => 0.1
        ),
        array(
          'label' => 'Active IP Blocks',
          'data' => $ipBlocksData,
          'backgroundColor' => 'rgba(139, 92, 246, 0.2)',
          'borderColor' => 'rgba(139, 92, 246, 1)',
          'borderWidth' => 2,
          'fill' => true,
          'tension' => 0.1
        )
      )
    )
  );

  wp_send_json_success($response);
  die();
}
