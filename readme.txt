=== HTTP Auth ===
Contributors: sasiddiqui
Tags: http-auth, brute attack, brute-force, restrict site, prevent-crawl
Requires at least: 4.9
Tested up to: 6.8
Stable tag: 1.0.1
License: GPLv3
License URI: https://www.gnu.org/licenses/gpl-3.0.html

Provides comprehensive security during development by protecting your entire site and your admin pages from brute-force attacks.

== Description ==

This plugin empowers you to set up HTTP Authentication for your website. This adds an extra layer of security by requiring a username and password to access specific areas.

Here's how it benefits you:

* **Enhanced Admin Security:** Shield your admin pages from brute-force attacks by adding a login barrier.
* **Controlled Crawling:** Restrict crawlers from accessing your site during development, preventing unnecessary indexing.
* **Post-Launch Access Control:** Maintain control over admin page access even after your website goes live.
* **Easy Activation/Deactivation:** Conveniently enable or disable HTTP Auth without deactivating the plugin entirely.
* **Per Environment Rules:** Protect only the admin on production and the complete site on development or staging, each with its own credentials.
* **Multisite Ready:** Manage the protection of every site in the network from the Network Admin.

=== Help Us Improve! ===

I am constantly working to enhance this plugin and your feedback is valuable. If you are happy with the plugin, consider leaving a review on WordPress.org. Your positive feedback motivates us to keep improving!

Link to Reviews: [https://wordpress.org/support/plugin/http-auth/reviews/?rate=5#new-post]()

=== Bug Reports ===

We welcome bug reports for HTTP Auth on GitHub: [https://github.com/samiahmedsiddiqui/http-auth](https://github.com/samiahmedsiddiqui/http-auth). Please remember that GitHub is primarily for bug reporting, and issues not classified as genuine bugs may be closed.

== Installation ==

This process defines you the steps to follow either you are installing through WordPress or Manually from FTP.

=== From within WordPress ===

1. Visit 'Plugins > Add New'
2. Search for HTTP Auth
3. Activate HTTP Auth from your Plugins page.
4. Go to "after activation" below.

=== Manually ===

1. Upload the `http-auth` folder to the `/wp-content/plugins/` directory
2. Activate HTTP Auth through the 'Plugins' menu in WordPress
3. Go to "after activation" below.

=== After activation ===

1. Go to the plugin settings page and set up the plugin for your site.
2. You're done!

== Frequently Asked Questions ==

= What is the difference between the Admin Site and Complete Site tabs? =

Each tab has its own site URLs, credentials, message and activation.

* **Admin Site** protects the login page, all admin pages and `xmlrpc.php`. `admin-ajax.php` and valid logout requests are not protected so the frontend keeps working. Use it for production and multisite.
* **Complete Site** protects every page. Use it for development, staging and other environments.

If a site is listed in both tabs, Complete Site is applied.

= How are the site URLs matched? =

Enter one URL per line without `http(s)://`, e.g. `example.com` or `example.com/blog` for a subdirectory site. Use `*` as a wildcard, e.g. `*.example.com`. URLs are matched against the site URL configured in WordPress, so the same database can be copied between production and staging and each environment gets its own protection.

= How does it work on multisite? =

When network activated, the settings page is available in the Network Admin and the URL lists cover every site in the network.

= I got locked out after too many failed attempts. =

After 10 failed attempts from the same IP, requests are blocked for 15 minutes. Both values can be changed with the `http_auth_max_attempts` and `http_auth_lockout_duration` filters. Return `0` from `http_auth_max_attempts` to disable throttling.

= My site is behind a proxy or load balancer. =

All visitors may share the proxy IP, so use the `http_auth_client_ip` filter to return the real client IP (for example from a trusted `X-Forwarded-For` header).

= How can I skip HTTP Auth for some requests? =

Use the `http_auth_is_protected_request` filter and return `false` for the requests you want to allow.

== Changelog ==

= 1.0.1 - Dec 23, 24 =

Fix minor WPCS issues and change text for better understanding.

= 1.0.0 - July 28, 2023 =

	* Fix WPCS issues

= Earlier versions =

	* For the changelog of earlier versions, please refer to the separate changelog.txt file.
