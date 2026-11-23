<?php
/**
 * HTTP Auth settings storage, upgrades and site matching.
 *
 * @package HTTPAuth
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Read, save, migrate and match the plugin settings.
 */
class HTTP_Auth_Config {

	/**
	 * Option holding the settings. The pre 1.1.0 `http_auth_settings` option
	 * is left untouched so rolling back keeps working.
	 *
	 * @var string
	 */
	const OPTION = 'http_auth_config';

	/**
	 * Protection modes, strictest first.
	 *
	 * @var array
	 */
	const MODES = array( 'site', 'admin' );

	/**
	 * Constant which lists site URLs in code.
	 *
	 * @var string
	 */
	const URLS_CONSTANT = 'HTTP_AUTH_SITE_URLS';

	/**
	 * Option set on upgrade to show the settings moved notice once.
	 *
	 * @var string
	 */
	const MOVED_NOTICE = 'http_auth_moved_notice';

	/**
	 * Allowed URL pattern after normalizing.
	 *
	 * @var string
	 */
	const URL_PATTERN = '#^[a-z0-9*][a-z0-9*.-]*(:\d+)?(/[a-z0-9*._~%/-]*)?$#';

	/**
	 * Default settings of a mode.
	 *
	 * @var array
	 */
	private static $default_mode = array(
		'activate' => 'off',
		'username' => '',
		'password' => '',
		'message'  => '',
	);

	/**
	 * Get the default settings.
	 *
	 * @access public
	 * @since  1.1.0
	 *
	 * @return array
	 */
	public static function defaults() {
		return array(
			'modes'               => array(
				'site'  => self::$default_mode,
				'admin' => self::$default_mode,
			),
			'default'             => 'none',
			// Custom credentials of the default, like a site row.
			'default_credentials' => null,
			'sites'               => array(),
			'legacy_hash'         => '',
		);
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
	 * Get the stored option from the network or the current site.
	 *
	 * @access private
	 * @since  1.1.0
	 *
	 * @return mixed
	 */
	private static function get_raw() {
		if ( self::is_network_mode() ) {
			return get_site_option( self::OPTION );
		}

		return get_option( self::OPTION );
	}

	/**
	 * Store the option on the network or the current site.
	 *
	 * @access private
	 * @since  1.1.0
	 *
	 * @param array $settings Settings.
	 */
	private static function put_raw( $settings ) {
		$stored = array(
			'modes'       => $settings['modes'],
			// Encoded so search-replace on environment pushes can't rewrite URLs.
			'sites'       => self::encode(
				array(
					'default'             => $settings['default'],
					'default_credentials' => $settings['default_credentials'],
					'sites'               => array_values( $settings['sites'] ),
				)
			),
			'legacy_hash' => $settings['legacy_hash'],
		);

		if ( self::is_network_mode() ) {
			update_site_option( self::OPTION, $stored );
		} else {
			update_option( self::OPTION, $stored, true );
		}
	}

	/**
	 * Whether the settings moved notice should be shown.
	 *
	 * @access public
	 * @since  1.1.0
	 *
	 * @return bool
	 */
	public static function has_moved_notice() {
		return (bool) ( self::is_network_mode() ? get_site_option( self::MOVED_NOTICE ) : get_option( self::MOVED_NOTICE ) );
	}

	/**
	 * Show the settings moved notice.
	 *
	 * @access private
	 * @since  1.1.0
	 */
	private static function add_moved_notice() {
		if ( self::is_network_mode() ) {
			update_site_option( self::MOVED_NOTICE, 1 );
		} else {
			update_option( self::MOVED_NOTICE, 1, false );
		}
	}

	/**
	 * Stop showing the settings moved notice.
	 *
	 * @access public
	 * @since  1.1.0
	 */
	public static function delete_moved_notice() {
		if ( self::is_network_mode() ) {
			delete_site_option( self::MOVED_NOTICE );
		} else {
			delete_option( self::MOVED_NOTICE );
		}
	}

	/**
	 * Encode data so its text can't be matched by search-replace.
	 *
	 * @access private
	 * @since  1.1.0
	 *
	 * @param array $data Data.
	 *
	 * @return string
	 */
	private static function encode( $data ) {
		// phpcs:ignore WordPress.PHP.DiscouragedPHPFunctions.obfuscation_base64_encode
		return base64_encode( wp_json_encode( $data ) );
	}

	/**
	 * Decode data stored by encode().
	 *
	 * @access private
	 * @since  1.1.0
	 *
	 * @param mixed $value Encoded value.
	 *
	 * @return array
	 */
	private static function decode( $value ) {
		if ( ! is_string( $value ) || '' === $value ) {
			return array();
		}

		// phpcs:ignore WordPress.PHP.DiscouragedPHPFunctions.obfuscation_base64_decode
		$data = json_decode( (string) base64_decode( $value, true ), true );

		return is_array( $data ) ? $data : array();
	}

	/**
	 * Get the settings with stored site rows (code URLs not merged).
	 *
	 * @access public
	 * @since  1.1.0
	 *
	 * @return array
	 */
	public static function get_settings() {
		return self::parse( self::get_raw() );
	}

	/**
	 * Turn a stored option into settings with defaults applied.
	 *
	 * @access private
	 * @since  1.1.0
	 *
	 * @param mixed $raw Stored option.
	 *
	 * @return array
	 */
	private static function parse( $raw ) {
		$settings = self::defaults();
		if ( ! is_array( $raw ) ) {
			return $settings;
		}

		foreach ( self::MODES as $mode ) {
			if ( isset( $raw['modes'][ $mode ] ) && is_array( $raw['modes'][ $mode ] ) ) {
				$settings['modes'][ $mode ] = wp_parse_args( $raw['modes'][ $mode ], self::$default_mode );
			}
		}

		$sites = isset( $raw['sites'] ) ? self::decode( $raw['sites'] ) : array();
		if ( isset( $sites['default'] ) && 'admin' === $sites['default'] ) {
			$settings['default'] = 'admin';
		}

		if ( isset( $sites['default_credentials'] ) ) {
			$settings['default_credentials'] = self::sanitize_credentials( $sites['default_credentials'] );
		}

		if ( isset( $sites['sites'] ) && is_array( $sites['sites'] ) ) {
			foreach ( $sites['sites'] as $row ) {
				$row = self::sanitize_row( $row );
				if ( null !== $row ) {
					$settings['sites'][ $row['url'] ] = $row;
				}
			}
		}

		if ( isset( $raw['legacy_hash'] ) && is_string( $raw['legacy_hash'] ) ) {
			$settings['legacy_hash'] = $raw['legacy_hash'];
		}

		return $settings;
	}

	/**
	 * Validate a site row.
	 *
	 * @access public
	 * @since  1.1.0
	 *
	 * @param mixed $row Row with `url`, `mode` and `source`.
	 *
	 * @return array|null
	 */
	public static function sanitize_row( $row ) {
		if ( ! is_array( $row ) || ! isset( $row['url'] ) || ! is_string( $row['url'] ) ) {
			return null;
		}

		$url = self::normalize_url( $row['url'] );
		if ( ! self::is_valid_url( $url ) ) {
			return null;
		}

		$mode = isset( $row['mode'] ) && in_array( $row['mode'], self::MODES, true ) ? $row['mode'] : 'none';

		$clean = array(
			'url'    => $url,
			'mode'   => $mode,
			'source' => isset( $row['source'] ) && 'code' === $row['source'] ? 'code' : 'settings',
		);

		$credentials = isset( $row['credentials'] ) ? self::sanitize_credentials( $row['credentials'] ) : null;
		if ( null !== $credentials ) {
			$clean['credentials'] = $credentials;
		}

		return $clean;
	}

	/**
	 * Validate custom credentials.
	 *
	 * @access public
	 * @since  1.1.0
	 *
	 * @param mixed $credentials Array with `username`, `password` (hash) and `message`.
	 *
	 * @return array|null Null unless both username and password are set.
	 */
	public static function sanitize_credentials( $credentials ) {
		if ( ! is_array( $credentials ) ) {
			return null;
		}

		$clean = array();
		foreach ( array( 'username', 'password', 'message' ) as $key ) {
			$clean[ $key ] = isset( $credentials[ $key ] ) && is_string( $credentials[ $key ] ) ? $credentials[ $key ] : '';
		}

		if ( '' === $clean['username'] || '' === $clean['password'] ) {
			return null;
		}

		return $clean;
	}

	/**
	 * Save settings.
	 *
	 * @access public
	 * @since  1.1.0
	 *
	 * @param array $settings Settings from get_settings().
	 */
	public static function save_settings( $settings ) {
		$current = self::get_settings();

		$settings                = wp_parse_args( $settings, self::defaults() );
		$settings['legacy_hash'] = $current['legacy_hash'];

		self::put_raw( $settings );
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
		$url = strtolower( trim( (string) $url ) );
		$url = preg_replace( '#^[a-z][a-z0-9+.-]*://#', '', $url );

		return rtrim( $url, '/' );
	}

	/**
	 * Whether a normalized URL is valid.
	 *
	 * @access public
	 * @since  1.1.0
	 *
	 * @param string $url Normalized URL.
	 *
	 * @return bool
	 */
	public static function is_valid_url( $url ) {
		return '' !== $url && 1 === preg_match( self::URL_PATTERN, $url );
	}

	/**
	 * Get the site URLs defined in code.
	 *
	 * @access public
	 * @since  1.1.0
	 *
	 * @return array Normalized URLs.
	 */
	public static function get_code_urls() {
		$urls = array();
		if ( defined( self::URLS_CONSTANT ) ) {
			$urls = constant( self::URLS_CONSTANT );
			if ( is_string( $urls ) ) {
				// A comma or line separated string works too.
				$urls = preg_split( '/[\s,]+/', $urls );
			}
		}

		/**
		 * Filters the site URLs defined in code.
		 *
		 * @since 1.1.0
		 *
		 * @param array $urls Site URLs.
		 */
		$urls = apply_filters( 'http_auth_site_urls', is_array( $urls ) ? $urls : array() );

		$clean = array();
		foreach ( (array) $urls as $url ) {
			$url = self::normalize_url( $url );
			if ( self::is_valid_url( $url ) ) {
				$clean[ $url ] = $url;
			}
		}

		return array_values( $clean );
	}

	/**
	 * Get all site rows: code URLs first, then URLs added in the settings.
	 *
	 * @access public
	 * @since  1.1.0
	 *
	 * @param array $settings Settings from get_settings().
	 *
	 * @return array Rows keyed by URL.
	 */
	public static function get_sites( $settings ) {
		$rows = array();
		foreach ( self::get_code_urls() as $url ) {
			$row = isset( $settings['sites'][ $url ] ) ? $settings['sites'][ $url ] : array( 'mode' => 'none' );

			$row['url']    = $url;
			$row['source'] = 'code';
			$rows[ $url ]  = $row;
		}

		// Rows of URLs removed from code are skipped.
		foreach ( $settings['sites'] as $url => $row ) {
			if ( ! isset( $rows[ $url ] ) && 'settings' === $row['source'] ) {
				$rows[ $url ] = $row;
			}
		}

		return $rows;
	}

	/**
	 * Get the current site URLs from WordPress, not the spoofable Host header.
	 *
	 * @access public
	 * @since  1.1.0
	 *
	 * @return array
	 */
	public static function get_current_urls() {
		return array_values(
			array_unique(
				array(
					self::normalize_url( home_url() ),
					self::normalize_url( site_url() ),
				)
			)
		);
	}

	/**
	 * Find the row which applies to the current site.
	 *
	 * Exact URLs win over wildcards, then the most specific wildcard, then the
	 * stricter mode.
	 *
	 * @access public
	 * @since  1.1.0
	 *
	 * @param array $rows Rows from get_sites().
	 *
	 * @return array|null Matched row, null if only the default applies.
	 */
	public static function match_site( $rows ) {
		$current = self::get_current_urls();
		$best    = null;
		$score   = null;
		$ranks   = array(
			'site'  => 2,
			'admin' => 1,
			'none'  => 0,
		);

		foreach ( $rows as $row ) {
			$is_wildcard = false !== strpos( $row['url'], '*' );
			if ( ! $is_wildcard ) {
				if ( in_array( $row['url'], $current, true ) ) {
					return $row;
				}
				continue;
			}

			$pattern = '#^' . str_replace( '\*', '[^/]*', preg_quote( $row['url'], '#' ) ) . '$#';
			foreach ( $current as $url ) {
				if ( preg_match( $pattern, $url ) ) {
					$row_score = array( strlen( str_replace( '*', '', $row['url'] ) ), $ranks[ $row['mode'] ] );
					if ( null === $score || $row_score > $score ) {
						$best  = $row;
						$score = $row_score;
					}
					break;
				}
			}
		}

		return $best;
	}

	/**
	 * Whether a mode is activated and has credentials.
	 *
	 * @access public
	 * @since  1.1.0
	 *
	 * @param array $mode_settings Settings of a mode.
	 *
	 * @return bool
	 */
	public static function is_mode_enabled( $mode_settings ) {
		return ! self::is_disabled()
			&& 'on' === $mode_settings['activate']
			&& '' !== $mode_settings['username']
			&& '' !== $mode_settings['password'];
	}

	/**
	 * Get the mode applied to the current site.
	 *
	 * @access public
	 * @since  1.1.0
	 *
	 * @param array $settings Settings from get_settings().
	 *
	 * @return string `site`, `admin` or `none`.
	 */
	public static function get_current_mode( $settings ) {
		$row = self::match_site( self::get_sites( $settings ) );

		return null === $row ? $settings['default'] : $row['mode'];
	}

	/**
	 * Get the settings applied to a site row, or to the default when null.
	 *
	 * @access public
	 * @since  1.1.0
	 *
	 * @param array      $settings Settings from get_settings().
	 * @param array|null $row      Row from get_sites(), null for the default.
	 *
	 * @return array|null Settings with `mode` and `custom`, null if not protected.
	 */
	public static function get_row_config( $settings, $row ) {
		$mode = null === $row ? $settings['default'] : $row['mode'];
		if ( 'none' === $mode || self::is_disabled() || 'on' !== $settings['modes'][ $mode ]['activate'] ) {
			return null;
		}

		// Custom credentials replace the mode ones, the mode's switch still applies.
		$config      = $settings['modes'][ $mode ];
		$credentials = null === $row ? $settings['default_credentials'] : ( isset( $row['credentials'] ) ? $row['credentials'] : null );
		$custom      = null !== $credentials;

		if ( $custom ) {
			$config['username'] = $credentials['username'];
			$config['password'] = $credentials['password'];
			if ( '' !== $credentials['message'] ) {
				$config['message'] = $credentials['message'];
			}
		}

		if ( '' === $config['username'] || '' === $config['password'] ) {
			return null;
		}

		$config['mode']   = $mode;
		$config['custom'] = $custom;

		return $config;
	}

	/**
	 * Get the settings applied to the current request.
	 *
	 * @access public
	 * @since  1.1.0
	 *
	 * @return array|null Settings with `mode`, null if not protected.
	 */
	public static function get_current_config() {
		$settings = self::get_settings();

		return self::get_row_config( $settings, self::match_site( self::get_sites( $settings ) ) );
	}

	/**
	 * Whether any mode is activated.
	 *
	 * @access public
	 * @since  1.1.0
	 *
	 * @return bool
	 */
	public static function has_enabled_mode() {
		$settings = self::get_settings();
		foreach ( self::MODES as $mode ) {
			if ( self::is_mode_enabled( $settings['modes'][ $mode ] ) ) {
				return true;
			}
		}

		return false;
	}

	/**
	 * Import settings of older versions.
	 *
	 * @access public
	 * @since  1.1.0
	 */
	public static function maybe_upgrade() {
		if ( self::is_network_mode() ) {
			self::maybe_upgrade_network();
			return;
		}

		$raw    = get_option( self::OPTION );
		$legacy = get_option( 'http_auth_settings' );
		$hash   = false === $legacy ? '' : md5( maybe_serialize( $legacy ) );

		$is_current = is_array( $raw ) && isset( $raw['modes'] );
		$unchanged  = false === $legacy
			|| ( is_array( $raw ) && isset( $raw['legacy_hash'] ) && $hash === $raw['legacy_hash'] );

		if ( $is_current && $unchanged ) {
			return;
		}

		if ( is_array( $raw ) && ! $is_current && $unchanged ) {
			// Flat settings saved by a development build of 1.1.0.
			$flat = $raw;
		} else {
			// First upgrade, or an older version changed them after a rollback.
			$flat = self::import_legacy_settings( $legacy );
		}

		$settings = self::apply_flat( self::parse( $raw ), $flat, home_url() );

		$settings['legacy_hash'] = $hash;
		self::put_raw( $settings );

		// First upgrade from an older version.
		if ( ! $is_current && ( false !== $legacy || false !== get_option( 'http_auth_username' ) ) ) {
			self::add_moved_notice();
		}
	}

	/**
	 * Build network settings from the settings of every site, once.
	 *
	 * @access private
	 * @since  1.1.0
	 */
	private static function maybe_upgrade_network() {
		$raw = get_site_option( self::OPTION );
		if ( is_array( $raw ) && isset( $raw['modes'] ) ) {
			return;
		}

		$settings = self::defaults();
		$imported = false;
		$main     = get_main_site_id();
		$site_ids = get_sites(
			array(
				'fields' => 'ids',
				'number' => 0,
			)
		);

		// Main site first so its credentials become the mode credentials.
		usort(
			$site_ids,
			function ( $a, $b ) use ( $main ) {
				return (int) ( (int) $b === $main ) - (int) ( (int) $a === $main );
			}
		);

		foreach ( $site_ids as $site_id ) {
			switch_to_blog( $site_id );

			$raw_site = get_option( self::OPTION );
			$legacy   = get_option( 'http_auth_settings' );
			if ( is_array( $raw_site ) && ! isset( $raw_site['modes'] ) ) {
				$flat = $raw_site;
			} elseif ( false !== $legacy || false !== get_option( 'http_auth_username' ) ) {
				$flat = self::import_legacy_settings( $legacy );
			} else {
				$flat = null;
			}
			$home_url = home_url();

			restore_current_blog();

			if ( null !== $flat ) {
				$settings = self::apply_flat( $settings, $flat, $home_url, true );
				$imported = true;
			}
		}

		self::put_raw( $settings );

		if ( $imported ) {
			self::add_moved_notice();
		}
	}

	/**
	 * Merge flat (pre 1.1.0) settings of one site into the settings.
	 *
	 * @access private
	 * @since  1.1.0
	 *
	 * @param array  $settings Settings.
	 * @param array  $flat     Flat settings with username, password (hash),
	 *                         message, apply and activate.
	 * @param string $home_url URL of the site the flat settings belong to.
	 * @param bool   $network  Whether merging one site of a network.
	 *
	 * @return array
	 */
	private static function apply_flat( $settings, $flat, $home_url, $network = false ) {
		$flat = wp_parse_args( $flat, self::$default_mode );
		$mode = isset( $flat['apply'] ) && 'admin' === $flat['apply'] ? 'admin' : 'site';
		$url  = self::normalize_url( $home_url );

		if ( ( '' === $flat['username'] && '' === $flat['password'] ) || ! self::is_valid_url( $url ) ) {
			return $settings;
		}

		$credentials = array(
			'username' => $flat['username'],
			'password' => $flat['password'],
			'message'  => $flat['message'],
		);
		$row         = array(
			'url'    => $url,
			'mode'   => $mode,
			'source' => 'settings',
		);

		if ( ! $network ) {
			$settings['modes'][ $mode ] = array_merge( $credentials, array( 'activate' => 'on' === $flat['activate'] ? 'on' : 'off' ) );
			$settings['sites'][ $url ]  = $row;

			return $settings;
		}

		// Network: the first site of a mode sets its credentials.
		if ( '' === $settings['modes'][ $mode ]['username'] ) {
			$settings['modes'][ $mode ] = array_merge( $credentials, array( 'activate' => 'on' ) );
		}

		if ( 'on' !== $flat['activate'] ) {
			$row['mode'] = 'none';
		}

		// Different credentials become the row's custom credentials.
		$current       = $settings['modes'][ $mode ];
		$same_password = isset( $flat['plain'] )
			? wp_check_password( $flat['plain'], $current['password'] )
			: $credentials['password'] === $current['password'];
		if ( $credentials['username'] !== $current['username'] || ! $same_password ) {
			$row['credentials'] = $credentials;
		}

		$settings['sites'][ $url ] = $row;

		return $settings;
	}

	/**
	 * Convert the settings of older versions to flat settings.
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
				return array();
			}

			return self::normalize_legacy( wp_unslash( self::decode_legacy( $legacy ) ) );
		}

		if ( is_array( $legacy ) ) {
			// Already converted by a development build of 1.1.0.
			if ( (int) get_option( 'http_auth_db_version', 0 ) >= 1 ) {
				return $legacy;
			}

			// 1.0.x: HTML-escaped values.
			return self::normalize_legacy( self::decode_legacy( $legacy ) );
		}

		// Before 0.3: separate options.
		$username = get_option( 'http_auth_username' );
		if ( false === $username ) {
			return array();
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
	 * Map legacy values to flat settings and hash the password.
	 *
	 * @access private
	 * @since  1.1.0
	 *
	 * @param array $legacy Decoded legacy settings.
	 *
	 * @return array
	 */
	private static function normalize_legacy( $legacy ) {
		$flat = self::$default_mode;

		foreach ( array( 'username', 'password', 'message' ) as $key ) {
			if ( isset( $legacy[ $key ] ) && is_string( $legacy[ $key ] ) ) {
				$flat[ $key ] = $legacy[ $key ];
			}
		}

		if ( '' !== $flat['password'] ) {
			// Plain text kept in memory only, to compare sites on network import.
			$flat['plain']    = $flat['password'];
			$flat['password'] = wp_hash_password( $flat['password'] );
		}

		// Older versions applied to the complete site unless set to admin.
		$flat['apply']    = isset( $legacy['apply'] ) && 'admin' === $legacy['apply'] ? 'admin' : 'site';
		$flat['activate'] = isset( $legacy['activate'] ) && 'on' === $legacy['activate'] ? 'on' : 'off';

		return $flat;
	}
}
