<?php
// public/class-shortcodes.php
if (! defined('ABSPATH')) {
  exit;
}

class Login_Sentinel_Shortcodes
{
  public function __construct()
  {
    add_shortcode('login_sentinel_stats', array($this, 'render_stats'));
  }

  public function render_stats($atts)
  {
    ob_start();
?>
    <div class="p-4 bg-gray-100 rounded login-sentinel-stats">
      <h2 class="text-xl font-bold"><?php _e('Login Sentinel Stats', 'login-sentinel'); ?></h2>
      <p><?php _e('Public statistics can be displayed here.', 'login-sentinel'); ?></p>
    </div>
<?php
    return ob_get_clean();
  }
}

new Login_Sentinel_Shortcodes();
