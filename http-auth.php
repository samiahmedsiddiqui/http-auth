<?php

/**
 * @package HttpAuth\Main
 */

/**
 * Plugin Name: Http Auth
 * Plugin URI: https://wordpress.org/plugins/http-auth/
 * Description: This plugin allows you apply HTTP Auth on your site. You can apply Http Authentication all over the site or only the admin pages.
 * Version:     0.2.1
 * Donate link: https://www.paypal.me/yasglobal
 * Author: YAS Global Team
 * Author URI: https://www.yasglobal.com/web-design-development/wordpress/http-auth/
 * License: GPL V3
 * Text Domain: http-auth
 */

/**
 * Copyright 2016-2017 and Sami Ahmed Siddiqui <sami.siddiqui@yasglobal.com>

 * This program is free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation; either version 2 of the License, or
 * (at your option) any later version.

 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.

 * You should have received a copy of the GNU General Public License
 * along with this program; if not, write to the Free Software
 * Foundation, Inc., 51 Franklin St, Fifth Floor, Boston, MA  02110-1301  USA
 */

// Make sure we don't expose any info if called directly
if ( ! defined( 'ABSPATH' ) ) {
  echo 'Hi there!  I\'m just a plugin, not much I can do when called directly.';
  exit;
}

if( !function_exists("add_action") || !function_exists("add_filter") ) {
  header( 'Status: 403 Forbidden' );
  header( 'HTTP/1.1 403 Forbidden' );
  exit();
}

if( !defined('HTTP_AUTH__PLUGIN_DIR') ) {
  define( 'HTTP_AUTH__PLUGIN_DIR', plugin_dir_path( __FILE__ ) );
}

if( !is_admin() ) {
  require_once(HTTP_AUTH__PLUGIN_DIR.'frontend/class.http-auth.php');   
  add_action( 'init', array( 'Http_Auth', 'init' ) );
} else {
  require_once(HTTP_AUTH__PLUGIN_DIR.'admin/class.http-auth-admin.php');
  add_action( 'init', array( 'Http_Auth_Admin', 'init' ) );

  $plugin = plugin_basename(__FILE__); 
  add_filter("plugin_action_links_$plugin", 'http_auth_settings_link' );
}

/**
 * Add Settings Link on plugin page
 */
function http_auth_settings_link($links) { 
  $settings_link = '<a href="admin.php?page=http-auth-settings">Settings</a>'; 
  array_unshift($links, $settings_link); 
  return $links; 
}
