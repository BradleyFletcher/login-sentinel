<?php
// admin/views/partials/table-ip-blocks.php
if (! defined('ABSPATH')) {
  exit;
}

global $wpdb;
$table_name = $wpdb->prefix . 'login_sentinel_ip_blocks';
// Retrieve all IP block records, ordered by blocked_time descending.
$blocks = $wpdb->get_results("SELECT * FROM $table_name ORDER BY blocked_time DESC LIMIT 10");
?>
<h2 class="my-4 text-xl font-bold"><?php esc_html_e('IP Blocks', 'login-sentinel'); ?></h2>
<table class="min-w-full bg-white border">
  <thead class="text-white bg-gray-700">
    <tr>
      <th class="w-1/4 py-2"><?php esc_html_e('Timeago', 'login-sentinel'); ?></th>
      <th class="w-1/4 py-2"><?php esc_html_e('IP Address', 'login-sentinel'); ?></th>
      <th class="w-1/4 py-2"><?php esc_html_e('Location', 'login-sentinel'); ?></th>
      <th class="w-1/4 py-2"><?php esc_html_e('Event', 'login-sentinel'); ?></th>
    </tr>
  </thead>
  <tbody id="blocks-tbody">
    <?php foreach ($blocks as $block) : ?>
      <?php
      $details = login_sentinel_get_location_details($block->ip_address);
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
      switch ($block->event) {
        case 'Blocked':
          $badgeClass = 'bg-yellow-100 text-yellow-800';
          break;
        case 'Expired':
          $badgeClass = 'bg-gray-300 text-gray-800';
          break;
        default:
          $badgeClass = 'bg-gray-100 text-gray-800';
          break;
      }
      ?>
      <tr class="text-center border-b">
        <td class="py-2"><?php echo esc_html(human_time_diff(strtotime($block->blocked_time), current_time('timestamp')) . ' ' . __('ago', 'login-sentinel')); ?></td>
        <td class="py-2"><?php echo esc_html($block->ip_address); ?></td>
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
            <?php echo esc_html($block->event); ?>
          </span>
        </td>
      </tr>
    <?php endforeach; ?>
  </tbody>
</table>
<div class="mt-4 text-center">
  <button id="view-more-blocks" class="px-4 py-2 font-bold text-white transition duration-300 transform bg-green-600 border border-b-4 border-green-800 rounded shadow-lg border-b-green-900 hover:bg-green-700 hover:scale-105">
    View More
  </button>
  <input type="hidden" id="blocks-offset" value="10">
</div>