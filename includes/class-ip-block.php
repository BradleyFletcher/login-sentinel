<?php
// includes/class-ip-block.php
if (! defined('ABSPATH')) {
  exit;
}

class Login_Sentinel_IP_Block
{
  public static function block_ip($ip, $event)
  {
    global $wpdb;
    $table_name = $wpdb->prefix . 'login_sentinel_ip_blocks';
    // Check if the IP is already blocked.
    $existing = $wpdb->get_var($wpdb->prepare("SELECT COUNT(*) FROM $table_name WHERE ip_address = %s", $ip));
    if ($existing > 0) {
      return;
    }
    // Optionally, retrieve location info (left blank here).
    $location = '';
    $wpdb->insert(
      $table_name,
      array(
        'ip_address'   => sanitize_text_field($ip),
        'location'     => sanitize_text_field($location),
        'blocked_time' => current_time('mysql'),
        'event'        => sanitize_text_field($event),
      ),
      array('%s', '%s', '%s', '%s')
    );
  }
}
