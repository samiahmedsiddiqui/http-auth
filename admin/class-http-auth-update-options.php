<?php
/**
 * @package HTTPAuth\Admin
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
	 */
	private function update_options() {
		$username = get_option( 'http_auth_username' );
		$password = get_option( 'http_auth_password' );
		$apply    = get_option( 'http_auth_apply' );
		if ( ! empty( $username ) && ! empty( $password ) && ! empty( $apply ) ) {
			$message  = get_option( 'http_auth_message' );
			$activate = get_option( 'http_auth_activate' );
			$http_auth_settings = array(
				'username' => $username,
				'password' => $password,
				'message'  => $message,
				'apply'    => $apply,
				'activate' => $activate
			);
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
