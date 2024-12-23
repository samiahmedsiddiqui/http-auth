<?php
/**
 * Plugin Name: HTTP Auth
 * Plugin URI: https://www.yasglobal.com/web-design-development/wordpress/http-auth/
 * Description: Secure your website from the Brute-force attack.
 * Version: 1.0.1
 * Requires at least: 3.5
 * Requires PHP: 5.6
 * Author: Sami Ahmed Siddiqui
 * Author URI: https://www.linkedin.com/in/sami-ahmed-siddiqui/
 * License: GPLv3
 * License URI: https://www.gnu.org/licenses/gpl-3.0.html
 *
 * Text Domain: http-auth
 * Domain Path: /languages/
 *
 * @package HTTPAuth
 */

/**
 *  HTTP Auth - Secure your website from the Brute-force attack
 *  Copyright 2016-2024 Sami Ahmed Siddiqui <sami.siddiqui@yasglobal.com>
 *
 *  This program is free software: you can redistribute it and/or modify
 *  it under the terms of the GNU General Public License as published by
 *  the Free Software Foundation, either version 3 of the License, or
 *  (at your option) any later version.

 *  This program is distributed in the hope that it will be useful,
 *  but WITHOUT ANY WARRANTY; without even the implied warranty of
 *  MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 *  GNU General Public License for more details.

 *  You should have received a copy of the GNU General Public License
 *  along with this program.  If not, see <http://www.gnu.org/licenses/>.
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

if ( ! defined( 'HTTP_AUTH_FILE' ) ) {
	define( 'HTTP_AUTH_FILE', __FILE__ );
}

// Include the main HTTP Auth class.
require_once plugin_dir_path( HTTP_AUTH_FILE ) . 'includes/class-http-auth.php';
