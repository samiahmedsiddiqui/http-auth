<?php
/**
 * @package HTTPAuth
 */

class HTTP_Auth {

	/**
	 * Class constructor.
	 */
	public function __construct() {
		add_action( 'init', array( $this, 'add_restriction' ) );
	}

	/**
	 * Pop up the http auth to the non-administrators if it is activated
	 */
	public function add_restriction() {
		$get_settings = get_option( 'http_auth_settings' );

		if ( isset( $get_settings ) && ! empty( $get_settings ) ) {
			$http_settings = unserialize( $get_settings );
			if ( $http_settings['activate'] == 'on' ) {
				if ( isset( $http_settings['apply'] )
					&& $http_settings['apply'] == 'admin' ) {
					if ( strpos( $_SERVER['REQUEST_URI'], '/wp-admin' ) === false
						&& strpos( $_SERVER['REQUEST_URI'], '/wp-login' ) === false ) {
						 return;
					}
				}
				$realm = 'Restricted Site';
				if ( strtolower( $_SERVER['SERVER_SOFTWARE'] ) == 'apache' ) {
					list( $_SERVER['PHP_AUTH_USER'], $_SERVER['PHP_AUTH_PW'] ) = explode(
						':', base64_decode( substr( $_SERVER['HTTP_AUTHORIZATION'], 6 ) )
					);
				}
				$asked_username = $asked_password = '';
				if ( isset( $_SERVER['PHP_AUTH_USER'] ) ) {
					$asked_username = $_SERVER['PHP_AUTH_USER'];
				}
				if ( isset( $_SERVER['PHP_AUTH_PW'] ) ) {
					$asked_password = $_SERVER['PHP_AUTH_PW'];
				}

				if ( ! ( $asked_username == $http_settings['username']
					&& $asked_password == $http_settings['password'] ) ) {
					$message = $http_settings['message'];
					header( 'WWW-Authenticate: Basic realm="' . $realm . '"' );
					header( 'HTTP/1.0 401 Unauthorized' );
					if ( empty( $message ) ) {
						 $message = 'This Site is Restricted. Please contact the administrator for access.';
					}
					die ( $this->cancel_page( $message ) );
				}
			}
		}
	}

  /**
   * Returns the Cancel Message
   */
	private function cancel_page( $message ) {
		$sitename = get_bloginfo ( 'name' );
		return '<html>
							<head>
								<meta http-equiv="Content-Type" content="text/html; charset=utf-8">
								<title>' . $sitename . ' | Restricted Site</title>
								<link rel="stylesheet" href="' . plugins_url( '/frontend/css/style.min.css', HTTP_AUTH_FILE ) . '" type="text/css">
							</head>
							<body class="http-restricted">
								<p>' . $message . '</p>
							</body>
					 </html>';
	}
}
