<?php
// includes/functions.php
if (! defined('ABSPATH')) {
  exit;
}

/**
 * Returns an HTML image tag for a country flag based on a country code.
 *
 * @param string $country_code Two-letter country code.
 * @return string HTML for the flag image.
 */
function login_sentinel_get_country_flag($country_code)
{
  if (empty($country_code)) {
    return '';
  }
  // Use SVG flags from the assets/images/flags directory.
  $flag_url = LOGIN_SENTINEL_PLUGIN_URL . 'assets/images/flags/' . strtolower($country_code) . '.svg';
  return '<img src="' . esc_url($flag_url) . '" alt="' . esc_attr($country_code) . '" class="inline-block w-5 h-5" />';
}

/**
 * Retrieves detailed location information (country and city) for an IP address using ip-api.com.
 *
 * The response is cached using a transient for 12 hours.
 *
 * @param string $ip The IP address to lookup.
 * @return array Associative array with keys 'country' and 'city' (or empty values if not available).
 */
function login_sentinel_get_location_details($ip)
{
  $cache_key = 'login_sentinel_location_details_' . md5($ip);
  $details = get_transient($cache_key);
  if (false !== $details) {
    return $details;
  }

  // Request status, countryCode, and city from ip-api.com.
  $url = 'http://ip-api.com/json/' . $ip . '?fields=status,countryCode,city';
  $response = wp_remote_get($url, array('timeout' => 5));
  if (is_wp_error($response)) {
    return array('country' => '', 'city' => '');
  }

  $body = wp_remote_retrieve_body($response);
  $data = json_decode($body, true);

  if (isset($data['status']) && $data['status'] === 'success') {
    $details = array(
      'country' => isset($data['countryCode']) ? $data['countryCode'] : '',
      'city'    => isset($data['city']) ? $data['city'] : ''
    );
    set_transient($cache_key, $details, 12 * HOUR_IN_SECONDS);
    return $details;
  }

  return array('country' => '', 'city' => '');
}
