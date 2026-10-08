<?php
/**
 * HTTPAuth Uninstall
 *
 * Deletes Settings on uninstalling the Plugin.
 *
 * @package HTTPAuth
 * @since   1.0.0
 */

if ( ! defined( 'WP_UNINSTALL_PLUGIN' ) ) {
	exit;
}

/**
 * Delete plugin options for the current site.
 */
function http_auth_delete_options() {
	delete_option( 'http_auth_settings' );
	delete_option( 'http_auth_plugin_version' );
	delete_option( 'http_auth_db_version' );
}

if ( is_multisite() ) {
	delete_site_option( 'http_auth_settings' );
	delete_site_option( 'http_auth_db_version' );

	$http_auth_site_ids = get_sites(
		array(
			'fields' => 'ids',
			'number' => 0,
		)
	);

	foreach ( $http_auth_site_ids as $http_auth_site_id ) {
		switch_to_blog( $http_auth_site_id );
		http_auth_delete_options();
		restore_current_blog();
	}
} else {
	http_auth_delete_options();
}

require_once __DIR__ . '/includes/class-http-auth-htaccess.php';
HTTP_Auth_Htaccess::remove_rules();
