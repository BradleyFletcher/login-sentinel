<?php
// admin/views/dashboard.php
if (! defined('ABSPATH')) {
  exit;
}

global $wpdb;
$attempts_table = $wpdb->prefix . 'login_sentinel_attempts';
$blocks_table   = $wpdb->prefix . 'login_sentinel_ip_blocks';

// Default date range for historical data (for the chart): last 30 days.
$default_start = date('Y-m-d', strtotime('-29 days'));
$default_end   = date('Y-m-d');

// For the Reset button, we reset to default "live" data:
// Live metrics are based on raw logs (last 24 hours for cards)
// and default historical chart data is for last 30 days.
$defaultResetStart = $default_start;
$defaultResetEnd   = $default_end;

// Chart data for last 30 days.
$chart_labels = array();
$chart_success_data = array();
$chart_failed_data = array();
$chart_blocked_data = array();
$chart_total_data = array();
$chart_active_data = array();

for ($i = 29; $i >= 0; $i--) {
  $date = date('Y-m-d', strtotime("-$i days"));
  $chart_labels[] = date('M j', strtotime($date));

  $day_success = intval($wpdb->get_var($wpdb->prepare(
    "SELECT COUNT(*) FROM $attempts_table WHERE event = %s AND DATE(time) = %s",
    'Success',
    $date
  )));

  $day_failed = intval($wpdb->get_var($wpdb->prepare(
    "SELECT COUNT(*) FROM $attempts_table WHERE event = %s AND DATE(time) = %s",
    'Failed',
    $date
  )));

  $day_blocked = intval($wpdb->get_var($wpdb->prepare(
    "SELECT COUNT(*) FROM $attempts_table WHERE event = %s AND DATE(time) = %s",
    'Blocked',
    $date
  )));

  $day_total = $day_success + $day_failed;

  // For IP blocks per day, we count all blocks with blocked_time on that day.
  // Note: In the dashboard chart we may show overall IP blocks (not just active) 
  // because a block may have been active on that day.
  $day_active = intval($wpdb->get_var($wpdb->prepare(
    "SELECT COUNT(*) FROM $blocks_table WHERE DATE(blocked_time) = %s",
    $date
  )));

  $chart_success_data[] = $day_success;
  $chart_failed_data[] = $day_failed;
  $chart_blocked_data[] = $day_blocked;
  $chart_total_data[] = $day_total;
  $chart_active_data[] = $day_active;
}

// Aggregated metrics for last 24 hours for metric cards.
$time_24 = date('Y-m-d H:i:s', strtotime('-24 hours'));
$agg_success = intval($wpdb->get_var($wpdb->prepare(
  "SELECT COUNT(*) FROM $attempts_table WHERE event = %s AND time >= %s",
  'Success',
  $time_24
)));
$agg_failed  = intval($wpdb->get_var($wpdb->prepare(
  "SELECT COUNT(*) FROM $attempts_table WHERE event = %s AND time >= %s",
  'Failed',
  $time_24
)));
$agg_blocked = intval($wpdb->get_var($wpdb->prepare(
  "SELECT COUNT(*) FROM $attempts_table WHERE event = %s AND time >= %s",
  'Blocked',
  $time_24
)));
$agg_total   = $agg_success + $agg_failed;

// Active IP Blocks: count only blocks that are still active.
// We use the block_duration setting to determine expiration.
$settings = get_option('login_sentinel_settings', array('block_duration' => 60));
$block_duration = intval($settings['block_duration']);
$agg_active = intval($wpdb->get_var($wpdb->prepare(
  "SELECT COUNT(*) FROM $blocks_table WHERE DATE_ADD(blocked_time, INTERVAL %d MINUTE) > NOW()",
  $block_duration
)));

// Prepare initial chart data.
$chart_data = array(
  'labels' => $chart_labels,
  'datasets' => array(
    array(
      'label' => 'Successful Logins',
      'data' => $chart_success_data,
      'backgroundColor' => 'rgba(16, 185, 129, 0.2)',
      'borderColor' => 'rgba(16, 185, 129, 1)',
      'borderWidth' => 2,
      'fill' => true,
      'tension' => 0.1
    ),
    array(
      'label' => 'Failed Logins',
      'data' => $chart_failed_data,
      'backgroundColor' => 'rgba(239, 68, 68, 0.2)',
      'borderColor' => 'rgba(239, 68, 68, 1)',
      'borderWidth' => 2,
      'fill' => true,
      'tension' => 0.1
    ),
    array(
      'label' => 'Blocked Logins',
      'data' => $chart_blocked_data,
      'backgroundColor' => 'rgba(234, 179, 8, 0.2)',
      'borderColor' => 'rgba(234, 179, 8, 1)',
      'borderWidth' => 2,
      'fill' => true,
      'tension' => 0.1
    ),
    array(
      'label' => 'Total Login Attempts',
      'data' => $chart_total_data,
      'backgroundColor' => 'rgba(59, 130, 246, 0.2)',
      'borderColor' => 'rgba(59, 130, 246, 1)',
      'borderWidth' => 2,
      'fill' => true,
      'tension' => 0.1
    ),
    array(
      'label' => 'Active IP Blocks',
      'data' => $chart_active_data,
      'backgroundColor' => 'rgba(139, 92, 246, 0.2)',
      'borderColor' => 'rgba(139, 92, 246, 1)',
      'borderWidth' => 2,
      'fill' => true,
      'tension' => 0.1
    )
  )
);

// --- Generate dynamic security recommendations based on metrics ---
$recommendations = "";
if ($agg_failed > 50) {
  $recommendations .= "High number of failed login attempts detected. Consider enabling reCAPTCHA or lowering your failed attempts threshold.<br>";
}
if ($agg_active > 20) {
  $recommendations .= "There are many active IP blocks, which may indicate persistent attack attempts. Review your block duration settings and consider tightening security further.<br>";
}
if (empty($recommendations)) {
  $recommendations = "There are currently no threats.";
}
?>
<!DOCTYPE html>
<html>

<head>
  <meta charset="UTF-8">
  <title>Login Sentinel Dashboard</title>
  <!-- Include Tailwind CSS from CDN if not already loaded -->
  <link href="https://cdn.jsdelivr.net/npm/tailwindcss@2.2.19/dist/tailwind.min.css" rel="stylesheet">
</head>

<body class="bg-gray-100">
  <div class="p-4 wrap">
    <!-- Header -->
    <div class="mb-8 text-center">
      <img src="<?php echo esc_url(LOGIN_SENTINEL_PLUGIN_URL . 'assets/images/lslogo.png'); ?>" alt="Login Sentinel Logo" class="w-auto h-12 mx-auto">
      <h1 class="mt-4 text-3xl font-bold text-gray-800">Login Sentinel Dashboard</h1>
      <p class="mt-2 text-lg">
        <a href="https://unifyr.io" class="text-green-600 underline" target="_blank" rel="noopener noreferrer">Unifyr.io</a>
      </p>
    </div>

    <!-- Top Row: Combined Date Range Picker, Protected Card, and Security Recommendations Card -->
    <div class="container grid grid-cols-1 gap-6 px-4 mx-auto mb-6 sm:grid-cols-3">
      <!-- Date Range Picker Card -->
      <div class="p-4 bg-white rounded-lg shadow">
        <div class="flex flex-col gap-2">
          <div class="flex flex-row gap-2">
            <div class="flex-1">
              <label for="start_date" class="block text-sm font-medium text-gray-800">Start Date</label>
              <input type="date" id="start_date" value="<?php echo esc_attr($default_start); ?>" class="block w-full mt-1 border-gray-300 rounded-md shadow-sm focus:border-green-600 focus:ring-green-600">
            </div>
            <div class="flex-1">
              <label for="end_date" class="block text-sm font-medium text-gray-800">End Date</label>
              <input type="date" id="end_date" value="<?php echo esc_attr($default_end); ?>" class="block w-full mt-1 border-gray-300 rounded-md shadow-sm focus:border-green-600 focus:ring-green-600">
            </div>
          </div>
          <div class="flex flex-row items-center justify-center gap-2 mt-2">
            <button id="apply-date-filter" class="px-3 py-1 font-semibold text-white transition duration-300 bg-green-600 border border-b-2 border-green-800 rounded shadow hover:bg-green-700">
              Apply Filter
            </button>
            <button id="reset-date-filter" class="px-3 py-1 font-semibold text-white transition duration-300 bg-gray-600 border border-b-2 border-gray-800 rounded shadow hover:bg-gray-700">
              Reset to Live Data
            </button>
          </div>
          <div id="date-filter-message" class="mt-1 text-xs text-center text-gray-600">
            <div class="text-green-600">Showing live metrics (last 24 hours)</div>
          </div>
        </div>
      </div>
      <!-- Protected Card (Original Reverted Design) -->
      <div class="flex items-center justify-center p-4 text-center bg-white rounded-lg shadow">
        <div class="flex flex-col items-center">
          <svg xmlns="http://www.w3.org/2000/svg" class="w-8 h-8 mb-1 text-green-600 animate-pulse" fill="none" viewBox="0 0 24 24" stroke="currentColor">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 22s8-4 8-10V5l-8-3-8 3v7c0 6 8 10 8 10z" />
          </svg>
          <h3 class="text-lg font-bold text-green-800">Protected</h3>
          <p class="text-xs text-green-600">Login Sentinel is active</p>
        </div>
      </div>
      <!-- Redesigned Security Recommendations Card -->
      <div class="flex flex-col items-center justify-center p-4 text-center rounded-lg shadow-md bg-gradient-to-r from-white to-green-50">
        <div class="mb-2">
          <div class="inline-flex items-center justify-center p-2 bg-green-600 rounded-full">
            <img src="<?php echo esc_url(LOGIN_SENTINEL_PLUGIN_URL . 'assets/images/info.svg'); ?>" alt="Security Info Icon" class="w-6 h-6" style="filter: brightness(0) invert(1);">
          </div>
        </div>
        <h2 class="text-xl font-bold text-center text-green-800">Security Recommendations</h2>
        <div class="mt-1 text-xs text-center text-gray-700">
          <?php echo wp_kses_post($recommendations); ?>
        </div>
      </div>
    </div>

    <!-- Second Row: Metric Cards for Last 24 Hours -->
    <div class="container grid grid-cols-1 gap-6 px-4 mx-auto mb-6 sm:grid-cols-2 md:grid-cols-5" id="metric-cards">
      <div class="flex flex-col items-center p-4 transition bg-white border border-gray-200 rounded-lg shadow hover:shadow-lg">
        <span class="text-sm text-gray-500 uppercase">Successful Logins</span>
        <span class="mt-2 text-4xl font-extrabold text-green-600" id="card-success"><?php echo number_format($agg_success); ?></span>
      </div>
      <div class="flex flex-col items-center p-4 transition bg-white border border-gray-200 rounded-lg shadow hover:shadow-lg">
        <span class="text-sm text-gray-500 uppercase">Failed Logins</span>
        <span class="mt-2 text-4xl font-extrabold text-red-600" id="card-failed"><?php echo number_format($agg_failed); ?></span>
      </div>
      <div class="flex flex-col items-center p-4 transition bg-white border border-gray-200 rounded-lg shadow hover:shadow-lg">
        <span class="text-sm text-gray-500 uppercase">Blocked Logins</span>
        <span class="mt-2 text-4xl font-extrabold text-yellow-600" id="card-blocked"><?php echo number_format($agg_blocked); ?></span>
      </div>
      <div class="flex flex-col items-center p-4 transition bg-white border border-gray-200 rounded-lg shadow hover:shadow-lg">
        <span class="text-sm text-gray-500 uppercase">Total Login Attempts</span>
        <span class="mt-2 text-4xl font-extrabold text-blue-600" id="card-total"><?php echo number_format($agg_total); ?></span>
      </div>
      <div class="flex flex-col items-center p-4 transition bg-white border border-gray-200 rounded-lg shadow hover:shadow-lg">
        <span class="text-sm text-gray-500 uppercase">Active IP Blocks</span>
        <span class="mt-2 text-4xl font-extrabold text-purple-600" id="card-active"><?php echo number_format($agg_active); ?></span>
      </div>
    </div>

    <!-- Chart Container for Historical Data -->
    <div class="mb-6">
      <canvas id="login-sentinel-chart" style="max-width: 100%; height: 400px;"></canvas>
    </div>

    <!-- Log Tables (Static) -->
    <div id="metrics-container">
      <?php
      include LOGIN_SENTINEL_PLUGIN_DIR . 'admin/views/partials/table-login-attempts.php';
      include LOGIN_SENTINEL_PLUGIN_DIR . 'admin/views/partials/table-ip-blocks.php';
      ?>
    </div>
  </div>
  <script type="text/javascript">
    var loginSentinelChartData = <?php echo wp_json_encode($chart_data); ?>;
    var loginSentinelLoadMoreNonce = "<?php echo esc_js(wp_create_nonce('login_sentinel_load_more_logs_nonce')); ?>";
    var loginSentinelGetHistoricalMetricsNonce = "<?php echo esc_js(wp_create_nonce('login_sentinel_get_historical_metrics')); ?>";
    var defaultResetStart = "<?php echo esc_js($defaultResetStart); ?>";
    var defaultResetEnd = "<?php echo esc_js($defaultResetEnd); ?>";
  </script>
</body>

</html>