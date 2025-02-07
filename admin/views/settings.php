<?php
// admin/views/settings.php
if (! defined('ABSPATH')) {
  exit;
}

// Process form submission.
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['login_sentinel_settings_nonce']) && wp_verify_nonce($_POST['login_sentinel_settings_nonce'], 'save_login_sentinel_settings')) {
  $failed_attempts_threshold = intval($_POST['failed_attempts_threshold']);
  $time_window               = intval($_POST['time_window']);
  $block_duration            = intval($_POST['block_duration']);
  $log_retention             = intval($_POST['log_retention']);
  $notification_email        = sanitize_email($_POST['notification_email']);
  $enable_notifications      = isset($_POST['enable_notifications']) ? 1 : 0;
  $email_frequency           = sanitize_text_field($_POST['email_frequency']);
  $disable_xmlrpc            = isset($_POST['disable_xmlrpc']) ? 1 : 0; // New option

  $settings = array(
    'failed_attempts_threshold' => $failed_attempts_threshold,
    'time_window'               => $time_window,
    'block_duration'            => $block_duration,
    'log_retention'             => $log_retention,
    'notification_email'        => $notification_email,
    'enable_notifications'      => $enable_notifications,
    'email_frequency'           => $email_frequency,
    'disable_xmlrpc'            => $disable_xmlrpc,
  );
  update_option('login_sentinel_settings', $settings);
  echo '<div class="p-4 mb-4 text-green-800 bg-green-100 rounded"><p>' . __('Settings saved.', 'login-sentinel') . '</p></div>';
}

$settings = get_option('login_sentinel_settings', array(
  'failed_attempts_threshold' => 5,
  'time_window'               => 15,
  'block_duration'            => 60,
  'log_retention'             => 30,
  'notification_email'        => '',
  'enable_notifications'      => 0,
  'email_frequency'           => 'daily',
  'disable_xmlrpc'            => 0,
));
?>
<div class="p-4 wrap">
  <!-- Vertical Header with Logo, Title, and Plugin Link -->
  <div class="mb-8 text-center">
    <img src="<?php echo esc_url(LOGIN_SENTINEL_PLUGIN_URL . 'assets/images/lslogo.png'); ?>" alt="Login Sentinel Logo" class="w-auto h-12 mx-auto">
    <h1 class="mt-4 text-3xl font-bold text-gray-800"><?php _e('Login Sentinel Settings', 'login-sentinel'); ?></h1>
    <p class="mt-2 text-lg">
      <a href="https://unifyr.io" class="text-green-600 underline" target="_blank" rel="noopener noreferrer">Unifyr.io</a>
    </p>
  </div>

  <div class="max-w-3xl p-6 mx-auto bg-white rounded-lg shadow">
    <form method="post" action="">
      <?php wp_nonce_field('save_login_sentinel_settings', 'login_sentinel_settings_nonce'); ?>

      <!-- Failed Attempts Threshold -->
      <div class="mb-4">
        <label for="failed_attempts_threshold" class="block text-sm font-medium text-gray-800">
          <?php _e('Failed Attempts Threshold', 'login-sentinel'); ?>
        </label>
        <input type="number" name="failed_attempts_threshold" id="failed_attempts_threshold" value="<?php echo esc_attr($settings['failed_attempts_threshold']); ?>" class="block w-full mt-1 border-gray-300 rounded-md shadow-sm focus:border-gray-500 focus:ring-gray-500" min="1" />
        <p class="mt-1 text-xs text-gray-500">
          <?php _e('Number of failed attempts before blocking an IP.', 'login-sentinel'); ?>
        </p>
      </div>

      <!-- Time Window -->
      <div class="mb-4">
        <label for="time_window" class="block text-sm font-medium text-gray-800">
          <?php _e('Time Window (minutes)', 'login-sentinel'); ?>
        </label>
        <input type="number" name="time_window" id="time_window" value="<?php echo esc_attr($settings['time_window']); ?>" class="block w-full mt-1 border-gray-300 rounded-md shadow-sm focus:border-gray-500 focus:ring-gray-500" min="1" />
        <p class="mt-1 text-xs text-gray-500">
          <?php _e('Time window (in minutes) to consider failed attempts.', 'login-sentinel'); ?>
        </p>
      </div>

      <!-- Block Duration -->
      <div class="mb-4">
        <label for="block_duration" class="block text-sm font-medium text-gray-800">
          <?php _e('Block Duration (minutes)', 'login-sentinel'); ?>
        </label>
        <input type="number" name="block_duration" id="block_duration" value="<?php echo esc_attr($settings['block_duration']); ?>" class="block w-full mt-1 border-gray-300 rounded-md shadow-sm focus:border-gray-500 focus:ring-gray-500" min="1" />
        <p class="mt-1 text-xs text-gray-500">
          <?php _e('Duration (in minutes) for which an IP is blocked.', 'login-sentinel'); ?>
        </p>
      </div>

      <!-- Log Retention -->
      <div class="mb-4">
        <label for="log_retention" class="block text-sm font-medium text-gray-800">
          <?php _e('Log Retention (days)', 'login-sentinel'); ?>
        </label>
        <input type="number" name="log_retention" id="log_retention" value="<?php echo esc_attr($settings['log_retention']); ?>" class="block w-full mt-1 border-gray-300 rounded-md shadow-sm focus:border-gray-500 focus:ring-gray-500" min="1" />
        <p class="mt-1 text-xs text-gray-500">
          <?php _e('Number of days to keep log entries.', 'login-sentinel'); ?>
        </p>
      </div>

      <!-- Notification Email -->
      <div class="mb-4">
        <label for="notification_email" class="block text-sm font-medium text-gray-800">
          <?php _e('Notification Email', 'login-sentinel'); ?>
        </label>
        <input type="email" name="notification_email" id="notification_email" value="<?php echo esc_attr($settings['notification_email']); ?>" class="block w-full mt-1 border-gray-300 rounded-md shadow-sm focus:border-gray-500 focus:ring-gray-500" />
        <p class="mt-1 text-xs text-gray-500">
          <?php _e('Email address to receive periodic metrics.', 'login-sentinel'); ?>
        </p>
      </div>

      <!-- Email Frequency Dropdown -->
      <div class="mb-4">
        <label for="email_frequency" class="block text-sm font-medium text-gray-800">
          <?php _e('Email Frequency', 'login-sentinel'); ?>
        </label>
        <select name="email_frequency" id="email_frequency" class="block w-full mt-1 border-gray-300 rounded-md shadow-sm focus:border-gray-500 focus:ring-gray-500">
          <option value="daily" <?php selected($settings['email_frequency'], 'daily'); ?>><?php _e('Daily', 'login-sentinel'); ?></option>
          <option value="weekly" <?php selected($settings['email_frequency'], 'weekly'); ?>><?php _e('Weekly', 'login-sentinel'); ?></option>
          <option value="monthly" <?php selected($settings['email_frequency'], 'monthly'); ?>><?php _e('Monthly', 'login-sentinel'); ?></option>
        </select>
        <p class="mt-1 text-xs text-gray-500">
          <?php _e('Select how frequently you want to receive an email with metrics.', 'login-sentinel'); ?>
        </p>
      </div>

      <!-- Enable Email Notifications -->
      <div class="flex items-center mb-6">
        <input id="enable_notifications" name="enable_notifications" type="checkbox" <?php checked($settings['enable_notifications'], 1); ?> class="w-4 h-4 text-green-600 border-gray-300 rounded focus:ring-green-500">
        <label for="enable_notifications" class="block ml-2 text-sm text-gray-800">
          <?php _e('Enable Email Notifications', 'login-sentinel'); ?>
        </label>
      </div>

      <!-- Disable XML-RPC -->
      <div class="flex items-center mb-6">
        <input id="disable_xmlrpc" name="login_sentinel_settings[disable_xmlrpc]" type="checkbox" value="1" <?php checked(1, isset($settings['disable_xmlrpc']) ? $settings['disable_xmlrpc'] : 0); ?> class="w-4 h-4 text-green-600 border-gray-300 rounded focus:ring-green-500">
        <label for="disable_xmlrpc" class="block ml-2 text-sm text-gray-800">
          <?php _e('Disable XML-RPC', 'login-sentinel'); ?>
        </label>
        <p class="mt-1 ml-8 text-xs text-gray-500">
          <?php _e('Disabling XML-RPC can reduce exposure to brute force attacks if your site does not require it.', 'login-sentinel'); ?>
        </p>
      </div>

      <button type="submit" class="w-full px-4 py-2 font-bold text-white transition duration-300 transform bg-green-600 border border-b-4 border-green-800 rounded shadow-lg border-b-green-900 hover:bg-green-700 hover:scale-105">
        <?php _e('Save Settings', 'login-sentinel'); ?>
      </button>
    </form>
  </div>

  <!-- New Section: Send Metrics Email Now -->
  <div class="max-w-3xl p-6 mx-auto mt-8 bg-white rounded-lg shadow">
    <h2 class="mb-4 text-xl font-bold text-center text-gray-800">Send Metrics Email Now</h2>
    <form id="send-email-form" method="post" action="">
      <div class="flex flex-col items-center justify-center gap-4 sm:flex-row">
        <select id="email_frequency_manual" name="email_frequency_manual" class="border-gray-300 rounded-md shadow-sm focus:border-gray-500 focus:ring-gray-500">
          <option value="daily"><?php _e('Daily', 'login-sentinel'); ?></option>
          <option value="weekly"><?php _e('Weekly', 'login-sentinel'); ?></option>
          <option value="monthly"><?php _e('Monthly', 'login-sentinel'); ?></option>
        </select>
        <button type="submit" class="px-4 py-2 font-bold text-white transition duration-300 transform bg-green-600 border border-b-4 border-green-800 rounded shadow-lg border-b-green-900 hover:bg-green-700 hover:scale-105">
          <?php _e('Send Metrics Email', 'login-sentinel'); ?>
        </button>
      </div>
      <div id="send-email-message" class="mt-4 text-center text-gray-600"></div>
    </form>
  </div>
  <script type="text/javascript">
    var loginSentinelSendEmailNowNonce = "<?php echo esc_js(wp_create_nonce('login_sentinel_send_email_now_nonce')); ?>";
  </script>
</div>