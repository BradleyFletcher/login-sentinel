<?php
// includes/class-settings.php
if (! defined('ABSPATH')) {
  exit;
}

class Login_Sentinel_Settings
{
  private $settings;

  public function __construct()
  {
    $this->settings = get_option('login_sentinel_settings', array(
      'failed_attempts_threshold' => 5,
      'time_window'               => 15,
    ));
  }

  public function get($key, $default = null)
  {
    return isset($this->settings[$key]) ? $this->settings[$key] : $default;
  }

  public function update($new_settings)
  {
    $this->settings = array_merge($this->settings, $new_settings);
    update_option('login_sentinel_settings', $this->settings);
  }
}
