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
	 * Settings schema version, bumped when stored settings need migrating.
	 *
	 * @var int
	 */
	const DB_VERSION = 2;

	/**
	 * Settings tabs: `site` protects the complete site, `admin` the admin only.
	 *
	 * @var array
	 */
	const TABS = array( 'site', 'admin' );

	/**
	 * Default settings of a tab.
	 *
	 * @var array
	 */
	private static $default_tab = array(
		'activate' => 'off',
		'urls'     => array(),
		'username' => '',
		'password' => '',
		'message'  => '',
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
	 * Whether the plugin is network activated on multisite.
	 *
	 * @access public
	 * @since  1.1.0
	 *
	 * @return bool
	 */
	public static function is_network_mode() {
		if ( ! is_multisite() ) {
			return false;
		}

		$plugins = get_site_option( 'active_sitewide_plugins', array() );

		return isset( $plugins[ HTTP_AUTH_BASENAME ] );
	}

	/**
	 * Get an option from the network (network mode) or the current site.
	 *
	 * @access public
	 * @since  1.1.0
	 *
	 * @param string $name          Option name.
	 * @param mixed  $default_value Default value.
	 *
	 * @return mixed
	 */
	public static function get_storage_option( $name, $default_value = false ) {
		if ( self::is_network_mode() ) {
			return get_site_option( $name, $default_value );
		}

		return get_option( $name, $default_value );
	}

	/**
	 * Update an option on the network (network mode) or the current site.
	 *
	 * @access public
	 * @since  1.1.0
	 *
	 * @param string $name  Option name.
	 * @param mixed  $value Option value.
	 *
	 * @return bool
	 */
	public static function update_storage_option( $name, $value ) {
		if ( self::is_network_mode() ) {
			return update_site_option( $name, $value );
		}

		return update_option( $name, $value );
	}

	/**
	 * Get settings of all tabs merged with the defaults.
	 *
	 * @access public
	 * @since  1.1.0
	 *
	 * @return array
	 */
	public static function get_settings() {
		$settings = self::get_storage_option( 'http_auth_settings', array() );
		if ( ! is_array( $settings ) ) {
			$settings = array();
		}

		$result = array();
		foreach ( self::TABS as $tab ) {
			$result[ $tab ] = self::default_tab();
			if ( isset( $settings[ $tab ] ) && is_array( $settings[ $tab ] ) ) {
				$result[ $tab ] = wp_parse_args( $settings[ $tab ], $result[ $tab ] );
			}
		}

		return $result;
	}

	/**
	 * Get the default settings of a tab.
	 *
	 * @access public
	 * @since  1.1.0
	 *
	 * @return array
	 */
	public static function default_tab() {
		return self::$default_tab;
	}

	/**
	 * Whether a tab is activated and has credentials and URLs.
	 *
	 * @access public
	 * @since  1.1.0
	 *
	 * @param array $tab_settings Settings of a tab.
	 *
	 * @return bool
	 */
	public static function is_enabled( $tab_settings ) {
		return 'on' === $tab_settings['activate']
			&& '' !== $tab_settings['username']
			&& '' !== $tab_settings['password']
			&& ! empty( $tab_settings['urls'] );
	}

	/**
	 * Strip the scheme and trailing slash and lowercase a URL.
	 *
	 * @access public
	 * @since  1.1.0
	 *
	 * @param string $url URL.
	 *
	 * @return string
	 */
	public static function normalize_url( $url ) {
		$url = strtolower( trim( $url ) );
		$url = preg_replace( '#^[a-z][a-z0-9+.-]*://#', '', $url );

		return rtrim( $url, '/' );
	}

	/**
	 * Whether the current site URL matches one of the URLs.
	 *
	 * Uses the configured site URL, not the spoofable Host header.
	 *
	 * @access public
	 * @since  1.1.0
	 *
	 * @param array $urls Normalized URLs, `*` matches within a path segment.
	 *
	 * @return bool
	 */
	public static function matches_current_site( $urls ) {
		$current = array_unique(
			array(
				self::normalize_url( home_url() ),
				self::normalize_url( site_url() ),
			)
		);

		foreach ( $urls as $url ) {
			$pattern = '#^' . str_replace( '\*', '[^/]*', preg_quote( $url, '#' ) ) . '$#';
			foreach ( $current as $site_url ) {
				if ( preg_match( $pattern, $site_url ) ) {
					return true;
				}
			}
		}

		return false;
	}

	/**
	 * Get the tab which applies to the current site, `site` wins over `admin`.
	 *
	 * @access public
	 * @since  1.1.0
	 *
	 * @return array|null Array with `mode` and the tab settings, null if none.
	 */
	public static function get_current_config() {
		$settings = self::get_settings();
		foreach ( self::TABS as $tab ) {
			if ( self::is_enabled( $settings[ $tab ] )
				&& self::matches_current_site( $settings[ $tab ]['urls'] )
			) {
				return array_merge( $settings[ $tab ], array( 'mode' => $tab ) );
			}
		}

		return null;
	}

	/**
	 * Migrate stored settings to the current schema.
	 *
	 * @access public
	 * @since  1.1.0
	 */
	public static function maybe_upgrade() {
		$version = (int) self::get_storage_option( 'http_auth_db_version', 0 );
		if ( $version >= self::DB_VERSION ) {
			return;
		}

		$settings = self::get_storage_option( 'http_auth_settings' );
		$home_url = home_url();

		// Network activated: start from the main site settings.
		if ( false === $settings && self::is_network_mode() ) {
			$main_site = get_main_site_id();
			$settings  = get_blog_option( $main_site, 'http_auth_settings' );
			$version   = (int) get_blog_option( $main_site, 'http_auth_db_version', 0 );
			$home_url  = get_home_url( $main_site );
		}

		if ( is_string( $settings ) ) {
			// Old versions stored a serialized string.
			$settings = maybe_unserialize( $settings );
		}

		if ( is_array( $settings ) ) {
			if ( $version < 1 ) {
				$settings = self::upgrade_to_v1( $settings );
			}

			if ( $version < 2 ) {
				$settings = self::upgrade_to_v2( $settings, $home_url );
			}

			self::update_storage_option( 'http_auth_settings', $settings );
		}

		self::update_storage_option( 'http_auth_db_version', self::DB_VERSION );
	}

	/**
	 * Decode HTML-escaped values and hash the plain text password.
	 *
	 * @access private
	 * @since  1.1.0
	 *
	 * @param array $settings Settings.
	 *
	 * @return array
	 */
	private static function upgrade_to_v1( $settings ) {
		foreach ( array( 'username', 'password', 'message' ) as $key ) {
			$settings[ $key ] = isset( $settings[ $key ] )
				? wp_specialchars_decode( $settings[ $key ], ENT_QUOTES )
				: '';
		}

		if ( '' !== $settings['password'] ) {
			$settings['password'] = wp_hash_password( $settings['password'] );
		}

		return $settings;
	}

	/**
	 * Move the flat settings into the tab of the old `apply` value.
	 *
	 * @access private
	 * @since  1.1.0
	 *
	 * @param array  $settings Settings.
	 * @param string $home_url URL of the site the settings belong to.
	 *
	 * @return array
	 */
	private static function upgrade_to_v2( $settings, $home_url ) {
		$tab = isset( $settings['apply'] ) && 'site' === $settings['apply'] ? 'site' : 'admin';

		$tab_settings = self::default_tab();
		foreach ( array( 'activate', 'username', 'password', 'message' ) as $key ) {
			if ( isset( $settings[ $key ] ) ) {
				$tab_settings[ $key ] = $settings[ $key ];
			}
		}
		$tab_settings['urls'] = array( self::normalize_url( $home_url ) );

		$result         = array(
			'site'  => self::default_tab(),
			'admin' => self::default_tab(),
		);
		$result[ $tab ] = $tab_settings;

		return $result;
	}

	/**
	 * Whether any tab is activated.
	 *
	 * @access public
	 * @since  1.1.0
	 *
	 * @return bool
	 */
	public static function has_enabled_tab() {
		foreach ( self::get_settings() as $tab_settings ) {
			if ( self::is_enabled( $tab_settings ) ) {
				return true;
			}
		}

		return false;
	}

	/**
	 * Re-add the .htaccess rules on plugin activation if HTTP Auth is enabled.
	 *
	 * @access public
	 * @since  1.1.0
	 */
	public static function activate() {
		if ( self::has_enabled_tab() ) {
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
