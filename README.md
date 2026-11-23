# HTTP Auth

HTTP Basic Authentication for WordPress: protect admin pages on production and complete staging or development sites, per site and multisite ready.

[WordPress.org](https://wordpress.org/plugins/http-auth/) · [Support forum](https://wordpress.org/support/plugin/http-auth/) · [Packagist](https://packagist.org/packages/samiahmedsiddiqui/http-auth)

## Features

- **Sites:** set None, Admin Site or Complete Site for each site URL, with wildcards like `*.example.com` and a default (None or Admin Site) for any other site.
- **Admin Site** protects the login page, admin pages and XML-RPC. **Complete Site** protects every page.
- **Push safe:** the site list is stored so hosts like WP Engine, Kinsta and Pantheon can not rewrite it when copying between environments. Site URLs can also be defined in code.
- **Credentials per mode and per site**, hashed with `wp_hash_password()`.
- **Multisite:** network wide settings in Network Admin when network activated.
- Brute-force throttling, a `wp-config.php` lockout escape, no caching of protected pages, WP-Cron and WP-CLI never blocked.

Settings live in **Settings > HTTP Auth** (Network Admin > Settings > HTTP Auth on multisite).

## Installation

- **WordPress:** Plugins > Add New, search for "HTTP Auth".
- **Composer (Packagist):** `composer require samiahmedsiddiqui/http-auth`
- **Composer (WPackagist):** `composer require wpackagist-plugin/http-auth`

Composer installs use [`composer/installers`](https://github.com/composer/installers) to place the plugin in `wp-content/plugins/http-auth/`.

## Configuration in code

List every environment in `wp-config.php`, a plugin or `functions.php`, then choose the protection for each URL in the Sites tab:

```php
define( 'HTTP_AUTH_SITE_URLS', array( 'example.com', 'staging.example.com', 'dev.example.com' ) );
```

| Constant | Purpose |
|---|---|
| `HTTP_AUTH_SITE_URLS` | Site URLs, as an array or a comma separated string |
| `HTTP_AUTH_DISABLE` | `true` turns protection off without changing settings |

| Filter | Purpose |
|---|---|
| `http_auth_site_urls` | Add or change the site URLs defined in code |
| `http_auth_is_protected_request` | Return `false` to skip protection for a request |
| `http_auth_max_attempts` | Failed attempts allowed per IP (default `10`, `0` disables throttling) |
| `http_auth_lockout_duration` | Lockout in seconds (default 15 minutes) |
| `http_auth_client_ip` | Client IP used for throttling, e.g. behind a proxy |

## Development

Requirements: PHP 7.2+, Composer, Node 22.13+ (see `.nvmrc`).

```bash
composer install
npm ci
```

| Command | What it does |
|---|---|
| `composer lint` | PHP coding standards (WPCS, PHPCompatibility) |
| `composer format` | Fix coding standards automatically |
| `npm run lint:css` | Stylelint |
| `npm run build` | Minify `assets/css/*.css` |
| `npm version <x.y.z>` | Update the version everywhere and rebuild |

CI checks PHP syntax on 7.2 to 8.5, coding standards, stylelint and that the minified CSS is up to date. Every pull request gets a WordPress Playground preview button. Pushing a `v*` tag deploys to WordPress.org.

## Bug reports

Bug reports are welcome in [GitHub issues](https://github.com/samiahmedsiddiqui/http-auth/issues). For questions and help, please use the [support forum](https://wordpress.org/support/plugin/http-auth/).

## License

GPLv3 or later.
