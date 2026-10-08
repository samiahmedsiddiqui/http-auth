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
	public $version = HTTP_AUTH_VERSION;

	/**
	 * Default plugin settings.
	 *
	 * @var array
	 */
	private static $default_settings = array(
		'username' => '',
		'password' => '',
		'message'  => '',
		'apply'    => 'admin',
		'activate' => 'off',
	);

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
		include_once HTTP_AUTH_PATH . 'includes/class-http-auth-htaccess.php';
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
		add_action( 'init', array( __CLASS__, 'maybe_upgrade' ), 1 );

		register_activation_hook( HTTP_AUTH_FILE, array( __CLASS__, 'activate' ) );
		register_deactivation_hook( HTTP_AUTH_FILE, array( __CLASS__, 'deactivate' ) );
	}

	/**
	 * Get plugin settings merged with the defaults.
	 *
	 * @access public
	 * @since  1.1.0
	 *
	 * @return array
	 */
	public static function get_settings() {
		$settings = get_option( 'http_auth_settings', array() );
		if ( ! is_array( $settings ) ) {
			$settings = array();
		}

		return wp_parse_args( $settings, self::$default_settings );
	}

	/**
	 * Whether HTTP Auth is activated and has credentials to check against.
	 *
	 * @access public
	 * @since  1.1.0
	 *
	 * @param array $settings Plugin settings.
	 *
	 * @return bool
	 */
	public static function is_enabled( $settings ) {
		return 'on' === $settings['activate']
			&& '' !== $settings['username']
			&& '' !== $settings['password'];
	}

	/**
	 * Decode HTML-escaped values and hash the plain text password (pre 1.1.0).
	 *
	 * @access public
	 * @since  1.1.0
	 */
	public static function maybe_upgrade() {
		$db_version = get_option( 'http_auth_plugin_version', '0' );
		if ( version_compare( $db_version, '1.1.0', '>=' ) ) {
			return;
		}

		$settings = get_option( 'http_auth_settings' );
		if ( is_string( $settings ) ) {
			// Old versions stored a serialized string.
			$settings = maybe_unserialize( $settings );
		}

		if ( is_array( $settings ) ) {
			$settings = wp_parse_args( $settings, self::$default_settings );

			$settings['username'] = wp_specialchars_decode( $settings['username'], ENT_QUOTES );
			$settings['message']  = wp_specialchars_decode( $settings['message'], ENT_QUOTES );
			if ( '' !== $settings['password'] ) {
				$settings['password'] = wp_hash_password(
					wp_specialchars_decode( $settings['password'], ENT_QUOTES )
				);
			}

			update_option( 'http_auth_settings', $settings );
		}

		update_option( 'http_auth_plugin_version', HTTP_AUTH_VERSION );
	}

	/**
	 * Re-add the .htaccess rules on plugin activation if HTTP Auth is enabled.
	 *
	 * @access public
	 * @since  1.1.0
	 */
	public static function activate() {
		if ( self::is_enabled( self::get_settings() ) ) {
			HTTP_Auth_Htaccess::add_rules();
		}
	}

	/**
	 * Remove the .htaccess rules on plugin deactivation.
	 *
	 * @access public
	 * @since  1.1.0
	 */
	public static function deactivate() {
		HTTP_Auth_Htaccess::remove_rules();
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
