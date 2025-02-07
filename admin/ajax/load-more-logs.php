<?php

/**
 * Load More Logs AJAX Handler for Login Sentinel
 *
 * Handles AJAX requests for loading additional rows in the login attempts and IP blocks tables.
 *
 * @package Login_Sentinel
 */

if (! defined('ABSPATH')) {
  exit;
}

add_action('wp_ajax_login_sentinel_load_more_logs', 'login_sentinel_load_more_logs_callback');
function login_sentinel_load_more_logs_callback()
{
  // Check that nonce is provided.
  if (empty($_POST['nonce'])) {
    error_log('[Login Sentinel] load-more-logs: Nonce missing in AJAX request.');
    wp_send_json_error('Nonce missing.');
    wp_die();
  }
  // Verify the nonce.
  if (! wp_verify_nonce($_POST['nonce'], 'login_sentinel_load_more_logs_nonce')) {
    error_log('[Login Sentinel] load-more-logs: Invalid nonce. Received: ' . print_r($_POST['nonce'], true));
    wp_send_json_error('Invalid nonce.');
    wp_die();
  }

  $type = isset($_POST['type']) ? sanitize_text_field(wp_unslash($_POST['type'])) : '';
  $offset = isset($_POST['offset']) ? intval($_POST['offset']) : 0;

  global $wpdb;

  if ('attempts' === $type) {
    $table_name = $wpdb->prefix . 'login_sentinel_attempts';
    $results = $wpdb->get_results($wpdb->prepare("SELECT * FROM $table_name ORDER BY time DESC LIMIT 10 OFFSET %d", $offset));
    if ($results) {
      foreach ($results as $attempt) {
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
        echo '<tr class="text-center border-b">';
        echo '<td class="py-2">' . esc_html(human_time_diff(strtotime($attempt->time), current_time('timestamp')) . ' ' . __('ago', 'login-sentinel')) . '</td>';
        echo '<td class="py-2">' . esc_html($attempt->ip_address) . '</td>';
        echo '<td class="py-2">' . esc_html($attempt->user_identifier) . '</td>';
        echo '<td class="py-2"><div class="flex items-center justify-center">';
        if (! empty($country)) {
          echo login_sentinel_get_country_flag($country);
        }
        echo '<span class="ml-2 text-gray-700">' . $location_str . '</span>';
        echo '</div></td>';
        echo '<td class="py-2"><span class="inline-flex px-2 text-xs font-semibold leading-5 rounded-full ' . esc_attr($badgeClass) . '">' . esc_html($attempt->event) . '</span></td>';
        echo '</tr>';
      }
    }
  } elseif ('blocks' === $type) {
    $table_name = $wpdb->prefix . 'login_sentinel_ip_blocks';
    $results = $wpdb->get_results($wpdb->prepare("SELECT * FROM $table_name ORDER BY blocked_time DESC LIMIT 10 OFFSET %d", $offset));
    if ($results) {
      foreach ($results as $block) {
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
        switch ($block->event) {
          case 'Blocked':
            $badgeClass = 'bg-yellow-100 text-yellow-800';
            break;
          default:
            $badgeClass = 'bg-gray-100 text-gray-800';
            break;
        }
        echo '<tr class="text-center border-b">';
        echo '<td class="py-2">' . esc_html(human_time_diff(strtotime($block->blocked_time), current_time('timestamp')) . ' ' . __('ago', 'login-sentinel')) . '</td>';
        echo '<td class="py-2">' . esc_html($block->ip_address) . '</td>';
        echo '<td class="py-2"><div class="flex items-center justify-center">';
        if (! empty($country)) {
          echo login_sentinel_get_country_flag($country);
        }
        echo '<span class="ml-2 text-gray-700">' . $location_str . '</span>';
        echo '</div></td>';
        echo '<td class="py-2"><span class="inline-flex px-2 text-xs font-semibold leading-5 rounded-full ' . esc_attr($badgeClass) . '">' . esc_html($block->event) . '</span></td>';
        echo '</tr>';
      }
    }
  }
  die();
}
