<?php
// admin/ajax/chart-data-handler.php
if (! defined('ABSPATH')) {
  exit;
}

add_action('wp_ajax_login_sentinel_get_chart_data', 'login_sentinel_get_chart_data_callback');
function login_sentinel_get_chart_data_callback()
{
  if (! current_user_can('manage_options')) {
    wp_send_json_error('Unauthorized');
    wp_die();
  }

  $period = isset($_POST['period']) ? sanitize_text_field($_POST['period']) : 'weekly';

  switch ($period) {
    case 'weekly':
      $date_from = date('Y-m-d H:i:s', strtotime('-1 week'));
      break;
    case 'monthly':
      $date_from = date('Y-m-d H:i:s', strtotime('-1 month'));
      break;
    case 'yearly':
      $date_from = date('Y-m-d H:i:s', strtotime('-1 year'));
      break;
    case 'all':
    default:
      $date_from = '1970-01-01 00:00:00';
      break;
  }

  global $wpdb;
  $attempts_table = $wpdb->prefix . 'login_sentinel_attempts';
  $blocks_table   = $wpdb->prefix . 'login_sentinel_ip_blocks';

  $success = intval($wpdb->get_var($wpdb->prepare(
    "SELECT COUNT(*) FROM $attempts_table WHERE event = %s AND time >= %s",
    'Success',
    $date_from
  )));
  $failed = intval($wpdb->get_var($wpdb->prepare(
    "SELECT COUNT(*) FROM $attempts_table WHERE event = %s AND time >= %s",
    'Failed',
    $date_from
  )));
  $blocked = intval($wpdb->get_var($wpdb->prepare(
    "SELECT COUNT(*) FROM $attempts_table WHERE event = %s AND time >= %s",
    'Blocked',
    $date_from
  )));
  $total = $success + $failed;
  $active_blocks = intval($wpdb->get_var($wpdb->prepare(
    "SELECT COUNT(*) FROM $blocks_table WHERE blocked_time >= %s",
    $date_from
  )));

  $data = array(
    'success'       => $success,
    'failed'        => $failed,
    'total'         => $total,
    'blocked'       => $blocked,
    'active_blocks' => $active_blocks,
  );

  wp_send_json_success($data);
  wp_die();
}
