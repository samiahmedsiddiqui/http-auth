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
			$asked_username = sanitize_text_field(
				wp_unslash( $_SERVER['PHP_AUTH_USER'] )
			);
		}

		if ( isset( $_SERVER['PHP_AUTH_PW'] ) ) {
			$asked_password = sanitize_text_field(
				wp_unslash( $_SERVER['PHP_AUTH_PW'] )
			);
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
				esc_html( $message ),
				esc_html( $title ),
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
		$http_settings = get_option( 'http_auth_settings' );
		if ( is_string( $http_settings ) ) {
			$http_settings = maybe_unserialize( $http_settings );
		}

		if ( isset( $_SERVER, $_SERVER['REQUEST_URI'] ) && is_array( $http_settings ) ) {
			$request_uri = esc_url_raw( wp_unslash( $_SERVER['REQUEST_URI'] ) );

			if ( isset( $http_settings['activate'] ) && 'on' === $http_settings['activate'] ) {
				if ( isset( $http_settings['apply'] ) && 'admin' === $http_settings['apply'] ) {
					if ( false === strpos( $request_uri, '/wp-admin' )
						&& false === strpos( $request_uri, '/wp-login' )
					) {
						return;
					} elseif ( 0 === strpos( $request_uri, '/wp-login' ) ) {
            // phpcs:disable WordPress.Security.NonceVerification.Recommended
						if ( isset( $_REQUEST, $_REQUEST['action'], $_REQUEST['_wpnonce'] )
							&& 'logout' === $_REQUEST['action']
						) {
							return;
						}
					} elseif ( false !== strpos( $request_uri, '/wp-admin/admin-ajax.php' ) ) {
						return;
					}
				}

				if ( isset(
					$_SERVER['HTTP_AUTHORIZATION'],
					$_SERVER['SERVER_SOFTWARE'],
					$_SERVER['PHP_AUTH_USER'],
					$_SERVER['PHP_AUTH_PW']
				)
				) {
					$http_authorization = sanitize_text_field(
						wp_unslash( $_SERVER['HTTP_AUTHORIZATION'] )
					);
					$server_software    = sanitize_text_field(
						wp_unslash( $_SERVER['SERVER_SOFTWARE'] )
					);
					$php_auth_user      = sanitize_text_field(
						wp_unslash( $_SERVER['PHP_AUTH_USER'] )
					);
					$php_auth_password  = sanitize_text_field(
						wp_unslash( $_SERVER['PHP_AUTH_PW'] )
					);
					if ( 'apache' === strtolower( $server_software ) ) {
						list( $php_auth_user, $php_auth_password ) = explode(
							':',
              // phpcs:ignore WordPress.PHP.DiscouragedPHPFunctions.obfuscation_base64_decode
							base64_decode(
								substr( $http_authorization, 6 )
							)
						);
					}
				}

				$this->apply_auth( $http_settings );
			}
		}
	}
}
