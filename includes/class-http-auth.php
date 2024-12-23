<?php
/**
 * HTTP Auth setup.
 *
 * @package HTTPAuth
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Main HTTP Auth class.
 */
final class HTTP_Auth {
	/**
	 * HTTP Auth version.
	 *
	 * @var string
	 */
	public $version = '1.0.1';

	/**
	 * Class constructor.
	 */
	public function __construct() {
		$this->define_constants();
		$this->includes();
		$this->init_hooks();
	}

	/**
	 * Define HTTP Auth Constants.
	 *
	 * @since  1.0.0
	 * @access private
	 */
	private function define_constants() {
		$this->define( 'HTTP_AUTH_BASENAME', plugin_basename( HTTP_AUTH_FILE ) );
		$this->define( 'HTTP_AUTH_PATH', plugin_dir_path( HTTP_AUTH_FILE ) );
		$this->define( 'HTTP_AUTH_VERSION', $this->version );
	}

	/**
	 * Define constant if not set already.
	 *
	 * @since  1.0.0
	 * @access private
	 *
	 * @param string      $name  Constant name.
	 * @param string|bool $value Constant value.
	 */
	private function define( $name, $value ) {
		if ( ! defined( $name ) ) {
			define( $name, $value );
		}
	}

	/**
	 * Include required core files used in admin and on the frontend.
	 *
	 * @since  1.0.0
	 * @access private
	 */
	private function includes() {
		include_once HTTP_AUTH_PATH . 'includes/class-http-auth-frontend.php';
		include_once HTTP_AUTH_PATH . 'admin/class-http-auth-admin.php';

		new HTTP_Auth_Frontend();
		new HTTP_Auth_Admin();
	}

	/**
	 * Hook into actions and filters.
	 *
	 * @since  1.0.0
	 * @access private
	 */
	private function init_hooks() {
		add_action( 'plugins_loaded', array( $this, 'load_textdomain' ) );
	}

	/**
	 * Add textdomain hook for translation.
	 *
	 * @access public
	 * @since  0.3
	 */
	public function load_textdomain() {
		$dirname = rtrim( HTTP_AUTH_PATH, '/' );

		load_plugin_textdomain(
			'http-auth',
			false,
			wp_basename( $dirname ) . '/languages/'
		);
	}
}

new HTTP_Auth();
