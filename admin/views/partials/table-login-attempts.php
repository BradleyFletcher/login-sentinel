<?php
// admin/views/partials/table-login-attempts.php
if (! defined('ABSPATH')) {
  exit;
}

global $wpdb;
$table_name = $wpdb->prefix . 'login_sentinel_attempts';
$attempts = $wpdb->get_results("SELECT * FROM $table_name ORDER BY time DESC LIMIT 10");
?>
<h2 class="my-4 text-xl font-bold"><?php esc_html_e('Recent Login Attempts', 'login-sentinel'); ?></h2>
<table class="min-w-full bg-white border">
  <thead class="text-white bg-gray-700">
    <tr>
      <th class="w-1/5 py-2"><?php esc_html_e('Timeago', 'login-sentinel'); ?></th>
      <th class="w-1/5 py-2"><?php esc_html_e('IP Address', 'login-sentinel'); ?></th>
      <th class="w-1/5 py-2"><?php esc_html_e('User', 'login-sentinel'); ?></th>
      <th class="w-1/5 py-2"><?php esc_html_e('Location', 'login-sentinel'); ?></th>
      <th class="w-1/5 py-2"><?php esc_html_e('Event', 'login-sentinel'); ?></th>
    </tr>
  </thead>
  <tbody id="attempts-tbody">
    <?php foreach ($attempts as $attempt) : ?>
      <?php
      $details = login_sentinel_get_location_details($attempt->ip_address);
      $country = ! empty($details['country']) ? $details['country'] : '';
      $city    = ! empty($details['city']) ? $details['city'] : '';
      $location_str = '';
      if (! empty($city)) {
        $location_str .= esc_html($city);
      }
      if (! empty($country)) {
        if (! empty($location_str)) {
          $location_str .= ', ';
        }
        $location_str .= esc_html($country);
      }
      $badgeClass = '';
      switch ($attempt->event) {
        case 'Success':
          $badgeClass = 'bg-green-100 text-green-800';
          break;
        case 'Failed':
          $badgeClass = 'bg-red-100 text-red-800';
          break;
        case 'Blocked':
          $badgeClass = 'bg-yellow-100 text-yellow-800';
          break;
        default:
          $badgeClass = 'bg-gray-100 text-gray-800';
          break;
      }
      ?>
      <tr class="text-center border-b">
        <td class="py-2"><?php echo esc_html(human_time_diff(strtotime($attempt->time), current_time('timestamp')) . ' ' . __('ago', 'login-sentinel')); ?></td>
        <td class="py-2"><?php echo esc_html($attempt->ip_address); ?></td>
        <td class="py-2"><?php echo esc_html($attempt->user_identifier); ?></td>
        <td class="py-2">
          <div class="flex items-center justify-center">
            <?php if (! empty($country)) : ?>
              <?php echo login_sentinel_get_country_flag($country); ?>
            <?php endif; ?>
            <span class="ml-2 text-gray-700"><?php echo $location_str; ?></span>
          </div>
        </td>
        <td class="py-2">
          <span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full <?php echo esc_attr($badgeClass); ?>">
            <?php echo esc_html($attempt->event); ?>
          </span>
        </td>
      </tr>
    <?php endforeach; ?>
  </tbody>
</table>
<div class="mt-4 text-center">
  <button id="view-more-attempts" class="px-4 py-2 font-bold text-white transition duration-300 transform bg-green-600 border border-b-4 border-green-800 rounded shadow-lg border-b-green-900 hover:bg-green-700 hover:scale-105">
    View More
  </button>
  <input type="hidden" id="attempts-offset" value="10">
</div>