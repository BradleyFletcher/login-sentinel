=== Login Sentinel ===
Contributors: bradfletcher
Donate link: https://unifyr.io/donate
Plugin URI: https://unifyr.io/login-sentinel
Tags: security, firewall, login, brute force, geolocation, monitoring
Requires at least: 5.0
Tested up to: 6.7
Stable tag: 1.0
License: GPL2 or later
License URI: https://www.gnu.org/licenses/gpl-2.0.html

== Description ==
Login Sentinel is a robust security plugin for WordPress developed by Brad Fletcher. It monitors and logs every login attempt on your website, detects brute-force attacks, and automatically blocks suspicious IP addresses. The plugin captures detailed geolocation information—including both the city and country—for each login attempt, enabling administrators to quickly identify and mitigate potential threats. With an intuitive dashboard that displays real-time metrics, interactive charts, and comprehensive logs, Login Sentinel empowers you to secure your website by adjusting thresholds, block durations, log retention policies, and email notification settings.

== Installation ==
1. Upload the entire Login Sentinel plugin folder to the `/wp-content/plugins/` directory.
2. Activate the plugin via the WordPress admin dashboard.
3. Navigate to the Login Sentinel Dashboard to view login activity and security metrics.
4. Configure the plugin settings under the Login Sentinel Settings page to adjust thresholds, block duration, log retention, and email notifications as needed.

== Frequently Asked Questions ==
= How does Login Sentinel work? =
Login Sentinel logs every login attempt, capturing the IP address, username, time, and geolocation data (city and country). When the number of failed attempts from an IP address exceeds the specified threshold within a set time window, the plugin blocks that IP and records a "Blocked" event.

= Can I customize the plugin’s behavior? =
Yes. The settings page allows you to customize the failed attempts threshold, time window, block duration, log retention period, and email notifications. This flexibility lets you tailor the security measures to your website's needs.

= Which geolocation service is used? =
Login Sentinel uses the free ip-api.com service to retrieve detailed geolocation information, including the country code and city for each login attempt.

== Screenshots ==
1. Dashboard Overview – Displays real-time login metrics, interactive charts, and detailed logs.
2. Settings Page – Provides a user-friendly interface to configure security thresholds and notification settings.

== Changelog ==
= 1.0 =
* Initial release.
* Logs login attempts, detects brute-force attacks, automatically blocks suspicious IP addresses, and retrieves detailed geolocation data (city and country).
* Features an interactive dashboard and customizable settings.

== Upgrade Notice ==
= 1.0 =
Initial release.
