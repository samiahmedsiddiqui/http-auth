<?php
/**
 * Plugin Name: HTTP Auth
 * Plugin URI: https://wordpress.org/plugins/http-auth/
 * Description: Secure your website from the Brute-force attack.
 * Version: 0.3.2
 * Author: YAS Global Team
 * Author URI: https://www.yasglobal.com/web-design-development/wordpress/http-auth/
 * License: GPLv3
 *
 * Text Domain: http-auth
 * Domain Path: /languages/
 *
 * @package HTTPAuth
 */

/**
 * HTTP Auth - Secure your website from the Brute-force attack
 * Copyright 2016-2020 and Sami Ahmed Siddiqui <sami.siddiqui@yasglobal.com>
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

final class HTTP_Auth
{

    /**
     * Class constructor.
     */
    public function __construct()
    {
        $this->setup_constants();
        $this->includes();
    }

    /**
     * Setup plugin constants.
     *
     * @access private
     * @since 1.0.0
     */
    private function setup_constants()
    {
        if ( ! defined( 'HTTP_AUTH_PLUGIN_VERSION' ) ) {
            define( 'HTTP_AUTH_PLUGIN_VERSION', '0.3.2' );
        }

        if ( ! defined( 'HTTP_AUTH_FILE' ) ) {
            define( 'HTTP_AUTH_FILE', __FILE__ );
        }

        if ( ! defined( 'HTTP_AUTH_PATH' ) ) {
            define( 'HTTP_AUTH_PATH', plugin_dir_path( HTTP_AUTH_FILE ) );
        }

        if ( ! defined( 'HTTP_AUTH_BASENAME' ) ) {
            define( 'HTTP_AUTH_BASENAME', plugin_basename( HTTP_AUTH_FILE ) );
        }
    }

    /**
     * Include required files.
     *
     * @access private
     * @since 1.0.0
     */
    private function includes()
    {
        include_once HTTP_AUTH_PATH . 'frontend/class-http-auth-frontend.php';
        new HTTP_Auth_Frontend();

        if ( is_admin() ) {
            include_once HTTP_AUTH_PATH . 'admin/class-http-auth-admin.php';
            new HTTP_Auth_Admin();

            add_action( 'plugins_loaded', array( $this, 'load_textdomain' ) );
        }
    }

    /**
     * Add textdomain hook for translation.
     *
     * @access public
     * @since 0.3
     */
    public function load_textdomain() {
        /* No longer needed, will be removed in next version.
        if ( '0.3' > get_option( 'http_auth_plugin_version' ) ) {
            include_once HTTP_AUTH_PATH . 'admin/class-http-auth-update-options.php';
            new HTTP_Auth_Update_Options();
        }
        */

        $dirname = rtrim( HTTP_AUTH_PATH , '/' );

        load_plugin_textdomain( 'http-auth', FALSE,
            wp_basename( $dirname ) . '/languages/'
        );
    }
}

// Make sure we don't expose any info if called directly
if ( defined( 'ABSPATH' ) ) {
    new HTTP_Auth();
}
