<?php
/**
 * Plugin Name: HTTP Auth
 * Plugin URI: https://wordpress.org/plugins/http-auth/
 * Description: Keeps you to secure your whole site on the development time and admin pages from the Brute attack.
 * Version: 0.3.2
 * Author: YAS Global Team
 * Author URI: https://www.yasglobal.com/web-design-development/wordpress/http-auth/
 * Donate link: https://www.paypal.me/yasglobal
 * License: GPLv3
 *
 * Text Domain: http-auth
 * Domain Path: /languages/
 *
 * @package HTTPAuth
 */

/**
 * HTTP Auth - Secure site from Brute Attacks
 * Copyright 2016-2018 and Sami Ahmed Siddiqui <sami.siddiqui@yasglobal.com>
 *
 * This program is free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation; either version 2 of the License, or
 * (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with this program; if not, write to the Free Software
 * Foundation, Inc., 51 Franklin St, Fifth Floor, Boston, MA  02110-1301  USA
 */

// Make sure we don't expose any info if called directly
if ( ! defined( 'ABSPATH' ) ) {
	echo 'Hi there!  I\'m just a plugin, not much I can do when called directly.';
	exit;
}

if ( ! defined( 'HTTP_AUTH_FILE' ) ) {
	define( 'HTTP_AUTH_FILE', __FILE__ );
}

require_once( dirname( HTTP_AUTH_FILE ) . '/http-auth-main.php' );
