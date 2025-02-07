<?php
// admin/class-admin-menu.php
if (! defined('ABSPATH')) {
  exit;
}

class Login_Sentinel_Admin_Menu
{
  public function __construct()
  {
    add_action('admin_menu', array($this, 'register_menu'));
    add_action('admin_enqueue_scripts', array($this, 'enqueue_assets'));
  }

  public function register_menu()
  {
    add_menu_page(
      __('Login Sentinel', 'login-sentinel'),
      __('Login Sentinel', 'login-sentinel'),
      'manage_options',
      'login-sentinel-dashboard',
      array($this, 'dashboard_page'),
      'dashicons-shield',
      6
    );
    add_submenu_page(
      'login-sentinel-dashboard',
      __('Settings', 'login-sentinel'),
      __('Settings', 'login-sentinel'),
      'manage_options',
      'login-sentinel-settings',
      array($this, 'settings_page')
    );
  }

  public function enqueue_assets($hook)
  {
    // Only load assets on Login Sentinel admin pages.
    if (strpos($hook, 'login-sentinel') === false) {
      return;
    }
    // Enqueue Tailwind CSS from CDN.
    wp_enqueue_style('tailwind-css', 'https://cdn.jsdelivr.net/npm/tailwindcss@2.2.19/dist/tailwind.min.css', array(), '2.2.19');
    // Enqueue our custom admin CSS.
    wp_enqueue_style('login-sentinel-admin', LOGIN_SENTINEL_PLUGIN_URL . 'assets/css/admin.css', array(), '1.0');
    // Enqueue Chart.js from CDN.
    wp_enqueue_script('chart-js', 'https://cdn.jsdelivr.net/npm/chart.js', array(), '3.9.1', true);
    // Enqueue our custom admin JS, with Chart.js as dependency.
    wp_enqueue_script('login-sentinel-admin-js', LOGIN_SENTINEL_PLUGIN_URL . 'assets/js/admin.js', array('jquery', 'chart-js'), '1.0', true);
  }

  public function dashboard_page()
  {
    include LOGIN_SENTINEL_PLUGIN_DIR . 'admin/views/dashboard.php';
  }

  public function settings_page()
  {
    include LOGIN_SENTINEL_PLUGIN_DIR . 'admin/views/settings.php';
  }
}

new Login_Sentinel_Admin_Menu();
