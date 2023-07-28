<?php
/**
 * HTTPAuth Uninstall
 *
 * Deletes Settings on uninstalling the Plugin.
 *
 * @package HTTPAuth
 * @since   1.0.0
 */

if ( defined( 'WP_UNINSTALL_PLUGIN' ) ) {
	delete_option( 'http_auth_settings' );
	delete_option( 'http_auth_plugin_version' );
}
