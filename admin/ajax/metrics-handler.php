<?php
// admin/ajax/metrics-handler.php
if (! defined('ABSPATH')) {
  exit;
}

add_action('wp_ajax_login_sentinel_get_metrics', 'login_sentinel_get_metrics_callback');
function login_sentinel_get_metrics_callback()
{
  if (! current_user_can('manage_options')) {
    wp_send_json_error('Unauthorized');
    wp_die();
  }
  $period = isset($_POST['period']) ? sanitize_text_field($_POST['period']) : 'weekly';

  // Determine the starting date based on the selected period.
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
  $attempts_count = $wpdb->get_var($wpdb->prepare(
    "SELECT COUNT(*) FROM $attempts_table WHERE time >= %s",
    $date_from
  ));
  ob_start();
?>
  <div class="p-4 bg-gray-100 rounded">
    <h3 class="text-xl font-semibold"><?php _e('Metrics', 'login-sentinel'); ?></h3>
    <p><?php echo sprintf(__('Login Attempts: %d', 'login-sentinel'), intval($attempts_count)); ?></p>
  </div>
<?php
  $html = ob_get_clean();
  echo $html;
  wp_die();
}
