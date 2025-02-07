<?php
// includes/class-logger.php
if (! defined('ABSPATH')) {
  exit;
}

class Login_Sentinel_Logger
{
  public static function log_attempt($ip, $user_identifier, $event, $location = '')
  {
    global $wpdb;
    $table_name = $wpdb->prefix . 'login_sentinel_attempts';
    $wpdb->insert(
      $table_name,
      array(
        'time'            => current_time('mysql'),
        'ip_address'      => sanitize_text_field($ip),
        'user_identifier' => sanitize_text_field($user_identifier),
        'location'        => sanitize_text_field($location),
        'event'           => sanitize_text_field($event),
      ),
      array('%s', '%s', '%s', '%s', '%s')
    );
  }
}
