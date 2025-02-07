<?php
// includes/class-firewall.php
if (! defined('ABSPATH')) {
  exit;
}

class Login_Sentinel_Firewall
{
  protected $settings;

  public function __construct()
  {
    // Get settings with defaults.
    $this->settings = get_option('login_sentinel_settings', array(
      'failed_attempts_threshold' => 5,
      'time_window'               => 15, // minutes
    ));
    add_action('wp_login_failed', array($this, 'handle_failed_login'));
    add_action('wp_login', array($this, 'handle_successful_login'), 10, 2);
  }

  public function handle_failed_login($username)
  {
    $ip = $_SERVER['REMOTE_ADDR'];
    // Get detailed location info using the updated function.
    $details = login_sentinel_get_location_details($ip);
    // We'll log just the country code as location.
    $location = isset($details['country']) ? $details['country'] : '';

    // Log the failed attempt.
    Login_Sentinel_Logger::log_attempt($ip, $username, 'Failed', $location);

    // Check if this IP should be blocked.
    if ($this->should_block_ip($ip)) {
      // Block the IP.
      Login_Sentinel_IP_Block::block_ip($ip, 'Blocked');
      // Also log a "Blocked" attempt.
      Login_Sentinel_Logger::log_attempt($ip, $username, 'Blocked', $location);
    }
  }

  public function handle_successful_login($user_login, $user)
  {
    $ip = $_SERVER['REMOTE_ADDR'];
    $details = login_sentinel_get_location_details($ip);
    $location = isset($details['country']) ? $details['country'] : '';
    // Log the successful login.
    Login_Sentinel_Logger::log_attempt($ip, $user_login, 'Success', $location);
  }

  public function should_block_ip($ip)
  {
    global $wpdb;
    $attempts_table = $wpdb->prefix . 'login_sentinel_attempts';
    $time_window    = intval($this->settings['time_window']);
    $threshold      = intval($this->settings['failed_attempts_threshold']);
    $date_from      = date('Y-m-d H:i:s', strtotime("-{$time_window} minutes"));
    $failed_attempts = $wpdb->get_var($wpdb->prepare(
      "SELECT COUNT(*) FROM $attempts_table WHERE ip_address = %s AND event = %s AND time >= %s",
      $ip,
      'Failed',
      $date_from
    ));
    return ($failed_attempts >= $threshold);
  }
}

new Login_Sentinel_Firewall();
