<?php
/**
 * HTTP Auth update options.
 *
 * @package HTTPAuth
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Serialize everything at one point instead of make couple of DB Calls.
 */
class HTTP_Auth_Update_Options {

	/**
	 * Class constructor.
	 */
	public function __construct() {
		$this->update_options();
	}

	/**
	 * Add multiple fields data to single row using serialize it.
	 *
	 * @access private
	 * @since  0.3
	 */
	private function update_options() {
		$apply    = get_option( 'http_auth_apply' );
		$password = get_option( 'http_auth_password' );
		$username = get_option( 'http_auth_username' );

		if ( ! empty( $username ) && ! empty( $password ) && ! empty( $apply ) ) {
			$activate           = get_option( 'http_auth_activate' );
			$http_auth_settings = array(
				'username' => $username,
				'password' => $password,
				'message'  => $message,
				'apply'    => $apply,
				'activate' => $activate,
			);
			$message            = get_option( 'http_auth_message' );

			$http_auth_settings = serialize( $http_auth_settings );
			update_option( 'http_auth_settings', $http_auth_settings );

			delete_option( 'http_auth_username' );
			delete_option( 'http_auth_password' );
			delete_option( 'http_auth_message' );
			delete_option( 'http_auth_apply' );
			delete_option( 'http_auth_activate' );
		}

		update_option( 'http_auth_plugin_version', '0.3.2' );
	}
}
