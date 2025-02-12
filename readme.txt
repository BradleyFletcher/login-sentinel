=== Login Sentinel ===
Contributors: BradleyFletcher
Plugin URI: https://unifyr.io/login-sentinel
Donate link: https://unifyr.io/donate
Tags: login, security, firewall, brute-force, authentication
Requires at least: 5.0
Tested up to: 6.7
Stable tag: 0.2
License: GPLv2 or later
License URI: https://www.gnu.org/licenses/gpl-2.0.html

== Description ==
Login Sentinel is a comprehensive security plugin for WordPress that logs all login attempts, detects brute-force attacks, and automatically blocks suspicious IP addresses. It records detailed geolocation (city and country) for every attempt and provides an intuitive dashboard with real-time metrics, interactive charts, and logs. Customizable settings allow you to adjust thresholds, block durations, log retention, and email notifications.

== Installation ==
1. Upload the entire `login-sentinel` folder to the `/wp-content/plugins/` directory.
2. Activate the plugin through the 'Plugins' screen in WordPress.
3. Configure the plugin via the Login Sentinel settings page in your WordPress admin.
4. Optionally, use the “Send Metrics Email Now” button to test your email configuration.

== Frequently Asked Questions ==
= How do I adjust the login thresholds? =
You can change the number of failed login attempts required before an IP is blocked and adjust the block duration via the plugin’s settings page.
= How does email notification work? =
Login Sentinel sends automated metrics emails based on the configured frequency (daily, weekly, or monthly). You can also send an email on demand via the settings page.
= What happens to IP blocks after they expire? =
Expired IP blocks are marked as "Expired" (and retained for historical reference) so that only active blocks affect login behavior.

== Screenshots ==
1. Dashboard displaying real-time metrics and interactive charts.
2. Settings page with configurable thresholds and email options.
3. Log view showing detailed login attempts and IP block history.

== Changelog ==
= 0.2 =
* Updated Stable Tag to 0.2 to match the main plugin file’s version.
* Integrated automated email scheduling based on the email frequency setting.
* Improved dashboard metrics and email template for both automated and manual emails.
* Fixed minor bugs and improved overall performance.

== Upgrade Notice ==
= 0.2 =
This version updates Login Sentinel to version 0.2. Please back up your site and settings before upgrading.

== Arbitrary Extra Section ==
For further support, detailed documentation, or to report bugs, please visit: 
https://unifyr.io/login-sentinel
