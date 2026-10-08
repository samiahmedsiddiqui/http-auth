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
	 * Realm sent with the `WWW-Authenticate` header.
	 *
	 * @var string
	 */
	const REALM = 'Restricted Site';

	/**
	 * Class constructor.
	 */
	public function __construct() {
		add_action( 'init', array( $this, 'add_restriction' ) );
	}

	/**
	 * Check whether the current request is a valid logout request.
	 *
	 * @access private
	 * @since  1.1.0
	 *
	 * @return bool
	 */
	private function is_logout_request() {
		// phpcs:disable WordPress.Security.NonceVerification.Recommended
		if ( ! isset( $_GET['action'], $_REQUEST['_wpnonce'] )
			|| 'logout' !== $_GET['action']
		) {
			return false;
		}

		$nonce = sanitize_key( wp_unslash( $_REQUEST['_wpnonce'] ) );
		// phpcs:enable WordPress.Security.NonceVerification.Recommended

		return false !== wp_verify_nonce( $nonce, 'log-out' );
	}

	/**
	 * Check whether the current request is for the login page.
	 *
	 * @access private
	 * @since  1.1.0
	 *
	 * @return bool
	 */
	private function is_login_request() {
		if ( function_exists( 'is_login' ) && is_login() ) {
			return true;
		}

		return isset( $GLOBALS['pagenow'] ) && 'wp-login.php' === $GLOBALS['pagenow'];
	}

	/**
	 * Check whether the current request needs to be authenticated.
	 *
	 * @access private
	 * @since  1.1.0
	 *
	 * @param array $config Settings of the tab applied to this site.
	 *
	 * @return bool
	 */
	private function is_protected_request( $config ) {
		if ( wp_doing_cron() || ( defined( 'WP_CLI' ) && WP_CLI ) ) {
			return false;
		}

		if ( 'admin' !== $config['mode'] ) {
			$protected = true;
		} elseif ( wp_doing_ajax() ) {
			$protected = false;
		} elseif ( is_admin() ) {
			$protected = true;
		} elseif ( $this->is_login_request() ) {
			$protected = ! $this->is_logout_request();
		} else {
			$protected = defined( 'XMLRPC_REQUEST' ) && XMLRPC_REQUEST;
		}

		/**
		 * Filters whether the current request is protected by HTTP Auth.
		 *
		 * @since 1.1.0
		 *
		 * @param bool  $protected Whether the request requires authentication.
		 * @param array $config    Settings of the tab applied to this site.
		 */
		return (bool) apply_filters( 'http_auth_is_protected_request', $protected, $config );
	}

	/**
	 * Get credentials sent with the request (not sanitized, compared as-is).
	 *
	 * @access private
	 * @since  1.1.0
	 *
	 * @return array|null Array of username and password, null if not provided.
	 */
	private function get_credentials() {
		// phpcs:disable WordPress.Security.ValidatedSanitizedInput.InputNotSanitized
		if ( isset( $_SERVER['PHP_AUTH_USER'] ) ) {
			$password = '';
			if ( isset( $_SERVER['PHP_AUTH_PW'] ) ) {
				$password = wp_unslash( $_SERVER['PHP_AUTH_PW'] );
			}

			return array( wp_unslash( $_SERVER['PHP_AUTH_USER'] ), $password );
		}

		// PHP as CGI/FastCGI: read the header passed via .htaccess.
		foreach ( array( 'HTTP_AUTHORIZATION', 'REDIRECT_HTTP_AUTHORIZATION' ) as $key ) {
			if ( empty( $_SERVER[ $key ] ) ) {
				continue;
			}

			$header = wp_unslash( $_SERVER[ $key ] );
			if ( 0 !== stripos( $header, 'basic ' ) ) {
				return null;
			}

			// phpcs:ignore WordPress.PHP.DiscouragedPHPFunctions.obfuscation_base64_decode
			$decoded = base64_decode( substr( $header, 6 ), true );
			if ( false === $decoded || false === strpos( $decoded, ':' ) ) {
				return null;
			}

			return explode( ':', $decoded, 2 );
		}
		// phpcs:enable WordPress.Security.ValidatedSanitizedInput.InputNotSanitized

		return null;
	}

	/**
	 * Get the transient key which tracks failed attempts for the client IP.
	 *
	 * @access private
	 * @since  1.1.0
	 *
	 * @return string
	 */
	private function get_attempts_key() {
		$ip = '';
		if ( isset( $_SERVER['REMOTE_ADDR'] ) ) {
			$ip = sanitize_text_field( wp_unslash( $_SERVER['REMOTE_ADDR'] ) );
		}

		/**
		 * Filters the client IP used for throttling (e.g. behind a proxy).
		 *
		 * @since 1.1.0
		 *
		 * @param string $ip Client IP address.
		 */
		$ip = (string) apply_filters( 'http_auth_client_ip', $ip );

		return 'http_auth_attempts_' . md5( $ip );
	}

	/**
	 * Get failed attempts, shared across the network in network mode.
	 *
	 * @access private
	 * @since  1.1.0
	 *
	 * @param string $key Transient key.
	 *
	 * @return int
	 */
	private function get_attempts( $key ) {
		if ( HTTP_Auth::is_network_mode() ) {
			return (int) get_site_transient( $key );
		}

		return (int) get_transient( $key );
	}

	/**
	 * Store failed attempts, 0 deletes them.
	 *
	 * @access private
	 * @since  1.1.0
	 *
	 * @param string $key      Transient key.
	 * @param int    $attempts Failed attempts.
	 * @param int    $lockout  Lockout duration in seconds.
	 */
	private function set_attempts( $key, $attempts, $lockout ) {
		$network = HTTP_Auth::is_network_mode();

		if ( 0 === $attempts ) {
			if ( $network ) {
				delete_site_transient( $key );
			} else {
				delete_transient( $key );
			}
		} elseif ( $network ) {
			set_site_transient( $key, $attempts, $lockout );
		} else {
			set_transient( $key, $attempts, $lockout );
		}
	}

	/**
	 * Validate credentials against the stored settings.
	 *
	 * @access private
	 * @since  1.1.0
	 *
	 * @param array $credentials Username and password sent with the request.
	 * @param array $config      Settings of the tab applied to this site.
	 *
	 * @return bool
	 */
	private function is_valid( $credentials, $config ) {
		list( $username, $password ) = $credentials;

		$valid_username = hash_equals( (string) $config['username'], (string) $username );
		// Trimmed like wp_hash_password() does.
		$valid_password = wp_check_password( trim( (string) $password ), $config['password'] );

		return $valid_username && $valid_password;
	}

	/**
	 * Send the 401 response asking the browser for credentials.
	 *
	 * @access private
	 * @since  1.0.0
	 *
	 * @param array $config Settings of the tab applied to this site.
	 */
	private function deny( $config ) {
		$message = $config['message'];
		if ( '' === trim( $message ) ) {
			$message = __( 'This Site is Restricted. Please contact the administrator for access.', 'http-auth' );
		}

		$title = sprintf(
			// translators: %s is replaced with the site name.
			__( '%s | Restricted Site', 'http-auth' ),
			get_bloginfo( 'name' )
		);

		header( 'WWW-Authenticate: Basic realm="' . self::REALM . '", charset="UTF-8"' );

		wp_die(
			nl2br( esc_html( $message ) ),
			esc_html( $title ),
			array(
				'response' => 401,
			)
		);
	}

	/**
	 * Send the 429 response when the client exceeded the allowed attempts.
	 *
	 * @access private
	 * @since  1.1.0
	 *
	 * @param int $lockout Lockout duration in seconds.
	 */
	private function deny_too_many_attempts( $lockout ) {
		header( 'Retry-After: ' . (int) $lockout );

		wp_die(
			esc_html__( 'Too many failed login attempts. Please try again later.', 'http-auth' ),
			esc_html__( 'Too Many Requests', 'http-auth' ),
			array(
				'response' => 429,
			)
		);
	}

	/**
	 * Check HTTP Auth activated and then apply HTTP Authentication.
	 *
	 * @access public
	 * @since  0.1
	 */
	public function add_restriction() {
		$config = HTTP_Auth::get_current_config();
		if ( null === $config || ! $this->is_protected_request( $config ) ) {
			return;
		}

		/**
		 * Filters the failed attempts allowed per IP. 0 disables throttling.
		 *
		 * @since 1.1.0
		 *
		 * @param int $max_attempts Maximum failed attempts.
		 */
		$max_attempts = (int) apply_filters( 'http_auth_max_attempts', 10 );

		/**
		 * Filters the lockout duration in seconds.
		 *
		 * @since 1.1.0
		 *
		 * @param int $lockout Lockout duration in seconds.
		 */
		$lockout = (int) apply_filters( 'http_auth_lockout_duration', 15 * MINUTE_IN_SECONDS );

		$attempts_key = $this->get_attempts_key();
		$attempts     = (int) $this->get_attempts( $attempts_key );
		if ( $max_attempts > 0 && $attempts >= $max_attempts ) {
			$this->deny_too_many_attempts( $lockout );
		}

		$credentials = $this->get_credentials();
		if ( null === $credentials ) {
			$this->deny( $config );
		}

		if ( $this->is_valid( $credentials, $config ) ) {
			if ( $attempts > 0 ) {
				$this->set_attempts( $attempts_key, 0, $lockout );
			}

			return;
		}

		if ( $max_attempts > 0 ) {
			$this->set_attempts( $attempts_key, $attempts + 1, $lockout );
		}

		$this->deny( $config );
	}
}
