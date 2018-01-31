<?php
/**
 * @package HTTPAuth\Main
 */

// Make sure we don't expose any info if called directly
if ( ! defined( 'ABSPATH' ) ) {
	echo 'Hi there!  I\'m just a plugin, not much I can do when called directly.';
	exit;
}

if ( ! function_exists( 'add_action' ) || ! function_exists( 'add_filter' ) ) {
	header( 'Status: 403 Forbidden' );
	header( 'HTTP/1.1 403 Forbidden' );
	exit();
}

define( 'HTTP_AUTH_PLUGIN_VERSION', '0.3.2' );

if ( ! defined( 'HTTP_AUTH_PATH' ) ) {
	define( 'HTTP_AUTH_PATH', plugin_dir_path( HTTP_AUTH_FILE ) );
}

if ( ! defined( 'HTTP_AUTH_BASENAME' ) ) {
	define( 'HTTP_AUTH_BASENAME', plugin_basename( HTTP_AUTH_FILE ) );
}

if ( is_admin() ) {
	require_once( HTTP_AUTH_PATH . 'admin/class-http-auth-admin.php' );
	new HTTP_Auth_Admin();

	register_uninstall_hook( HTTP_AUTH_FILE, 'http_auth_plugin_uninstall' );
} else {
	require_once( HTTP_AUTH_PATH . 'frontend/class-http-auth.php' );
	new HTTP_Auth();
}

/**
 * Remove Option on uninstalling/deleting the Plugin.
 */
function http_auth_plugin_uninstall() {
	delete_option( 'http_auth_settings' );
	delete_option( 'http_auth_plugin_version' );
}

/**
 * Add textdomain hook for translation
 */
function http_auth_plugin_textdomain() {
	if ( HTTP_AUTH_PLUGIN_VERSION !== get_option( 'http_auth_plugin_version' ) ) {
		require_once(
			HTTP_AUTH_PATH . 'admin/class-http-auth-update-options.php'
		);
		new HTTP_Auth_Update_Options();
	}

	load_plugin_textdomain( 'http-auth', FALSE,
		basename( dirname( HTTP_AUTH_FILE ) ) . '/languages/'
	);
}
add_action( 'plugins_loaded', 'http_auth_plugin_textdomain' );
