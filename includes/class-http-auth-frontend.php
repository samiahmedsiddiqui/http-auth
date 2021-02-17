<?php
/**
 * HTTP Auth Frontend.
 *
 * @package HTTPAuth
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Class that triggers HTTP Auth.
 */
class HTTP_Auth_Frontend {

	/**
	 * Class constructor.
	 */
	public function __construct() {
		add_action( 'init', array( $this, 'add_restriction' ) );
	}

	/**
	 * Authenticate request before allowing access. Incase of unauthenticated
	 * requests, cancelled message shows up with 401 Unauthorized status.
	 *
	 * @access private
	 * @since  1.0.0
	 *
	 * @param array $auth_settings configured settings for the plugin.
	 */
	private function apply_auth( $auth_settings ) {
		$asked_password = '';
		$asked_username = '';

		if ( isset( $_SERVER['PHP_AUTH_USER'] ) ) {
			$asked_username = $_SERVER['PHP_AUTH_USER'];
		}

		if ( isset( $_SERVER['PHP_AUTH_PW'] ) ) {
			$asked_password = $_SERVER['PHP_AUTH_PW'];
		}

		if ( ! ( $asked_username === $auth_settings['username']
			&& $asked_password === $auth_settings['password'] )
		) {
			$message = $auth_settings['message'];
			$title   = get_bloginfo( 'name' ) . ' | Restricted Site';
			header( 'WWW-Authenticate: Basic realm="Restricted Site"' );

			if ( empty( $message ) ) {
				$message = 'This Site is Restricted. Please contact the administrator for access.';
			}

			wp_die(
				$message,
				$title,
				array(
					'response' => 401,
				)
			);
		}
	}

	/**
	 * Check HTTP Auth activated and then apply HTTP Authentication.
	 *
	 * @access public
	 * @since  0.1
	 */
	public function add_restriction() {
		$get_settings = get_option( 'http_auth_settings' );

		if ( isset( $get_settings ) && ! empty( $get_settings ) ) {
			$http_settings = unserialize( $get_settings );
			if ( 'on' === $http_settings['activate'] ) {
				if ( isset( $http_settings['apply'] )
					&& 'admin' === $http_settings['apply']
				) {
					if ( false === strpos( $_SERVER['REQUEST_URI'], '/wp-admin' )
						&& false === strpos( $_SERVER['REQUEST_URI'], '/wp-login' )
					) {
						return;
					} elseif ( strpos( $_SERVER['REQUEST_URI'], '/wp-login' ) === 0 ) {
						if ( isset( $_REQUEST['action'] )
							&& $_REQUEST['action'] === 'logout'
							&& isset( $_REQUEST['_wpnonce'] )
						) {
							return;
						}
					} elseif ( strpos( $_SERVER['REQUEST_URI'], '/wp-admin/admin-ajax.php' ) !== false ) {
						return;
					}
				}

				if ( 'apache' === strtolower( $_SERVER['SERVER_SOFTWARE'] ) ) {
					list( $_SERVER['PHP_AUTH_USER'], $_SERVER['PHP_AUTH_PW'] ) = explode(
						':',
						base64_decode(
							substr( $_SERVER['HTTP_AUTHORIZATION'], 6 )
						)
					);
				}

				$this->apply_auth( $http_settings );
			}
		}
	}
}
