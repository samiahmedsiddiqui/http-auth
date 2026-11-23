=== HTTP Auth ===
Contributors: sasiddiqui
Tags: http auth, basic auth, password protect, staging, brute force
Requires at least: 4.9
Requires PHP: 7.2
Tested up to: 7.1
Stable tag: 1.1.0
License: GPLv3
License URI: https://www.gnu.org/licenses/gpl-3.0.html

HTTP Basic Auth for WordPress: protect admin pages on production and complete staging or dev sites, per site and multisite ready.

== Description ==

HTTP Auth adds a browser username and password prompt (HTTP Basic Authentication) in front of your site, before WordPress or any login form loads.

Use it to:

* **Protect production admin pages** from brute-force attacks: the login page, admin pages and XML-RPC ask for an extra username and password.
* **Hide development and staging sites** from visitors and search engines by protecting every page.
* **Use one database everywhere.** Choose the protection for each site URL, so production, staging and dev each get the right protection, even after your host copies the site between environments.

= Features =

* **Sites:** set None, Admin Site or Complete Site for each site URL, with wildcards like `*.example.com` and a default for any other site.
* **Push safe:** hosts like WP Engine, Kinsta and Pantheon rewrite URLs when copying a site. HTTP Auth stores its site list so it is not rewritten, and site URLs can also be defined in `wp-config.php` or code.
* **Credentials per mode and per site:** Admin Site and Complete Site have their own username, password and message, and any site can use its own instead.
* **Multisite:** when network activated, manage every site from Network Admin.
* **Brute-force throttling:** too many failed attempts from one IP are blocked for a while.
* **Lockout escape:** `define( 'HTTP_AUTH_DISABLE', true );` in `wp-config.php` turns it off without touching settings.
* **Secure storage:** passwords are hashed, never stored or shown in plain text.
* **Works with caching, WP-Cron and WP-CLI:** protected pages are not cached, and cron and CLI requests are never blocked.

=== Help Us Improve! ===

If you are happy with the plugin, please [leave a review on WordPress.org](https://wordpress.org/support/plugin/http-auth/reviews/?rate=5#new-post). Your feedback keeps it improving.

=== Bug Reports ===

Bug reports are welcome on [GitHub](https://github.com/samiahmedsiddiqui/http-auth/issues). For questions and help, please use the [support forum](https://wordpress.org/support/plugin/http-auth/).

== Installation ==

= From within WordPress =

1. Visit Plugins > Add New.
2. Search for HTTP Auth.
3. Install and activate HTTP Auth.

= Manually =

1. Upload the `http-auth` folder to the `/wp-content/plugins/` directory.
2. Activate HTTP Auth through the Plugins menu in WordPress.

= After activation =

1. Go to Settings > HTTP Auth (Network Admin > Settings > HTTP Auth when network activated).
2. In the Admin Site or Complete Site tab, set a username and password and turn on protection.
3. In the Sites tab, choose the protection for each site URL.

== Frequently Asked Questions ==

= Where are the settings? =

Settings > HTTP Auth. When network activated on multisite, Network Admin > Settings > HTTP Auth.

= I am locked out. How do I turn it off? =

Add this line to `wp-config.php`, above the "That's all, stop editing!" line:

`define( 'HTTP_AUTH_DISABLE', true );`

HTTP Auth stops asking for credentials until you remove the line. Your settings are kept.

= What is the difference between Admin Site and Complete Site? =

Each has its own credentials, message and activation.

* **Admin Site** protects the login page, all admin pages and `xmlrpc.php`. `admin-ajax.php` and valid logout requests are not protected so the frontend keeps working. Use it for production and multisite.
* **Complete Site** protects every page. Use it for development, staging and other environments.

In the **Sites** tab, choose None, Admin Site or Complete Site for each site URL. Sites not listed use the default, which can be None or Admin Site.

= Can a site use its own credentials? =

Yes. In the Sites tab, open "Mode credentials" under a site (or "Any other site"), tick "Use custom credentials" and set a username, password and optional message. That site then asks for these instead of its mode's credentials. Turning the mode off in its tab still turns that site's protection off.

= How are site URLs matched? =

URLs are written without `http(s)://`, e.g. `example.com` or `example.com/blog` for a subdirectory site. Use `*` as a wildcard, e.g. `*.example.com`. An exact URL wins over a wildcard, and a more specific wildcard wins over a broader one.

URLs are matched against the site URL configured in WordPress, never the address sent by the visitor.

= My host copies the site between production and staging. =

Hosts like WP Engine, Kinsta and Pantheon search-replace the site URL in the database when copying between environments. HTTP Auth stores its site list encoded so it is not rewritten, and each environment keeps its own protection.

You can also list every environment in `wp-config.php`, a plugin or your theme's `functions.php`, which is never rewritten:

`define( 'HTTP_AUTH_SITE_URLS', array( 'example.com', 'staging.example.com' ) );`

A comma separated string works too, and so does the `http_auth_site_urls` filter. Then choose the protection for each URL in the Sites tab.

= How does it work on multisite? =

When network activated, the settings are in Network Admin and the Sites tab covers every site in the network. Settings from each site of older versions are imported on upgrade, including sites that used different credentials.

= The prompt keeps coming back even with the right password. =

Your server probably runs PHP as CGI or FastCGI and does not pass the `Authorization` header to PHP.

On Apache, HTTP Auth adds this to `.htaccess` when you turn protection on. If `.htaccess` is not writable, add it yourself above the WordPress rules:

`RewriteEngine On`
`RewriteRule .* - [E=HTTP_AUTHORIZATION:%{HTTP:Authorization}]`

On Nginx, make sure the header reaches PHP in your PHP location block:

`fastcgi_param HTTP_AUTHORIZATION $http_authorization;`

= "Complete Site" is on but some pages open without a prompt. =

A page cache is serving copies saved before HTTP Auth was turned on. Protected pages are no longer cached, so clear your caching plugin and any host or CDN cache once after turning it on.

= I got locked out after too many failed attempts. =

After 10 failed attempts from the same IP, requests are blocked for 15 minutes. Both values can be changed with the `http_auth_max_attempts` and `http_auth_lockout_duration` filters. Return `0` from `http_auth_max_attempts` to disable throttling.

= My site is behind a proxy or load balancer. =

All visitors may share the proxy IP, so use the `http_auth_client_ip` filter to return the real client IP (for example from a trusted `X-Forwarded-For` header).

= How can I skip HTTP Auth for some requests? =

Use the `http_auth_is_protected_request` filter and return `false` for the requests you want to allow.

== Screenshots ==

1. Sites tab: choose None, Admin Site or Complete Site for each site URL, from code or added in settings.
2. A site with its own credentials.
3. Complete Site tab: credentials, message and the switch for every site set to Complete Site.
4. Admin Site tab: credentials, message and the switch for every site set to Admin Site.

== Changelog ==

= 1.1.0 =

* Sites tab: choose None, Admin Site or Complete Site for each site URL, with wildcards and a default for other sites.
* Site URLs can be defined in code with `HTTP_AUTH_SITE_URLS` or the `http_auth_site_urls` filter.
* The site list is stored so environment pushes (WP Engine, Kinsta, Pantheon) can not rewrite it.
* Custom credentials per site.
* Multisite: network wide settings in Network Admin, each site's old settings are imported.
* Settings moved under Settings > HTTP Auth, with a new design and a Support box. The About page was removed.
* Security: passwords are hashed and never shown, fixed a query string bypass of the admin only mode, XML-RPC is protected in Admin Site mode, failed attempts are throttled.
* Fixed passwords with special characters (`&`, `"`, `<`) never matching.
* Fixed CGI/FastCGI servers asking for the password again and again.
* WP-Cron and WP-CLI are no longer blocked.
* Protected pages are not cached.
* `HTTP_AUTH_DISABLE` turns protection off from `wp-config.php`.
* Upgrades from 0.3.x and 1.0.x keep the existing settings, and rolling back to 1.0.x keeps working.
* Requires PHP 7.2.

= 1.0.1 - Dec 23, 24 =

Fix minor WPCS issues and change text for better understanding.

= 1.0.0 - July 28, 2023 =

	* Fix WPCS issues

= Earlier versions =

	* For the changelog of earlier versions, please refer to the separate changelog.txt file.

== Upgrade Notice ==

= 1.1.0 =

Settings move to Settings > HTTP Auth and gain per-site protection. Existing settings are kept. Requires PHP 7.2. Admin Site mode now also protects XML-RPC.
