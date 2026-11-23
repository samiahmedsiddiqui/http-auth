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
	 * Option holding the settings. The pre 1.1.0 `http_auth_settings` option
	 * is left untouched so rolling back keeps working.
	 *
	 * @var string
	 */
	const OPTION = 'http_auth_config';

	/**
	 * Default plugin settings.
	 *
	 * @var array
	 */
	private static $default_settings = array(
		'username'    => '',
		'password'    => '',
		'message'     => '',
		'apply'       => 'admin',
		'activate'    => 'off',
		'legacy_hash' => '',
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
		$settings = get_option( self::OPTION, array() );
		if ( ! is_array( $settings ) ) {
			$settings = array();
		}

		return wp_parse_args( $settings, self::$default_settings );
	}

	/**
	 * Save plugin settings.
	 *
	 * @access public
	 * @since  1.1.0
	 *
	 * @param array $settings Plugin settings.
	 */
	public static function save_settings( $settings ) {
		$current = self::get_settings();

		$settings['legacy_hash'] = $current['legacy_hash'];

		update_option( self::OPTION, wp_parse_args( $settings, self::$default_settings ), true );
	}

	/**
	 * Whether HTTP Auth is turned off with the `HTTP_AUTH_DISABLE` constant.
	 *
	 * @access public
	 * @since  1.1.0
	 *
	 * @return bool
	 */
	public static function is_disabled() {
		return defined( 'HTTP_AUTH_DISABLE' ) && HTTP_AUTH_DISABLE;
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
		return ! self::is_disabled()
			&& 'on' === $settings['activate']
			&& '' !== $settings['username']
			&& '' !== $settings['password'];
	}

	/**
	 * Import the settings of older versions, again if an older version
	 * changed them after a rollback.
	 *
	 * @access public
	 * @since  1.1.0
	 */
	public static function maybe_upgrade() {
		$config = get_option( self::OPTION );
		$legacy = get_option( 'http_auth_settings' );
		$hash   = false === $legacy ? '' : md5( maybe_serialize( $legacy ) );

		if ( is_array( $config ) && ( false === $legacy
			|| ( isset( $config['legacy_hash'] ) && $hash === $config['legacy_hash'] ) )
		) {
			return;
		}

		$settings                = self::import_legacy_settings( $legacy );
		$settings['legacy_hash'] = $hash;

		update_option( self::OPTION, $settings, true );
	}

	/**
	 * Convert the settings of older versions.
	 *
	 * @access private
	 * @since  1.1.0
	 *
	 * @param mixed $legacy Value of the `http_auth_settings` option.
	 *
	 * @return array
	 */
	private static function import_legacy_settings( $legacy ) {
		if ( is_string( $legacy ) && '' !== $legacy ) {
			// 0.3.x: serialized string with HTML-escaped and slashed values.
			$legacy = maybe_unserialize( $legacy );
			if ( ! is_array( $legacy ) ) {
				return self::$default_settings;
			}

			return self::normalize_legacy( wp_unslash( self::decode_legacy( $legacy ) ) );
		}

		if ( is_array( $legacy ) ) {
			// Already converted by a development build of 1.1.0.
			if ( (int) get_option( 'http_auth_db_version', 0 ) >= 1 ) {
				return wp_parse_args( $legacy, self::$default_settings );
			}

			// 1.0.x: HTML-escaped values.
			return self::normalize_legacy( self::decode_legacy( $legacy ) );
		}

		// Before 0.3: separate options.
		$username = get_option( 'http_auth_username' );
		if ( false === $username ) {
			return self::$default_settings;
		}

		return self::normalize_legacy(
			array(
				'username' => $username,
				'password' => get_option( 'http_auth_password', '' ),
				'message'  => get_option( 'http_auth_message', '' ),
				'apply'    => get_option( 'http_auth_apply', '' ),
				'activate' => get_option( 'http_auth_activate', '' ),
			)
		);
	}

	/**
	 * Decode HTML-escaped legacy values.
	 *
	 * @access private
	 * @since  1.1.0
	 *
	 * @param array $legacy Legacy settings.
	 *
	 * @return array
	 */
	private static function decode_legacy( $legacy ) {
		foreach ( array( 'username', 'password', 'message' ) as $key ) {
			if ( isset( $legacy[ $key ] ) && is_string( $legacy[ $key ] ) ) {
				$legacy[ $key ] = wp_specialchars_decode( $legacy[ $key ], ENT_QUOTES );
			}
		}

		return $legacy;
	}

	/**
	 * Map legacy values to the current settings and hash the password.
	 *
	 * @access private
	 * @since  1.1.0
	 *
	 * @param array $legacy Decoded legacy settings.
	 *
	 * @return array
	 */
	private static function normalize_legacy( $legacy ) {
		$settings = self::$default_settings;

		foreach ( array( 'username', 'password', 'message' ) as $key ) {
			if ( isset( $legacy[ $key ] ) && is_string( $legacy[ $key ] ) ) {
				$settings[ $key ] = $legacy[ $key ];
			}
		}

		if ( '' !== $settings['password'] ) {
			$settings['password'] = wp_hash_password( $settings['password'] );
		}

		// Older versions applied to the complete site unless set to admin.
		$settings['apply']    = isset( $legacy['apply'] ) && 'admin' === $legacy['apply'] ? 'admin' : 'site';
		$settings['activate'] = isset( $legacy['activate'] ) && 'on' === $legacy['activate'] ? 'on' : 'off';

		return $settings;
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
