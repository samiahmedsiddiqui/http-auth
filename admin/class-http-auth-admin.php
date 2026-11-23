<?php
/**
 * HTTP Auth Admin.
 *
 * @package HTTPAuth
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Create admin menu, footer text, settings links etc.
 */
class HTTP_Auth_Admin {
	/**
	 * Initializes WordPress hooks.
	 */
	public function __construct() {
		if ( HTTP_Auth_Config::is_network_mode() ) {
			add_action( 'network_admin_menu', array( $this, 'admin_menu' ) );
			add_filter(
				'network_admin_plugin_action_links_' . HTTP_AUTH_BASENAME,
				array( $this, 'settings_link' )
			);
		} else {
			add_action( 'admin_menu', array( $this, 'admin_menu' ) );
			add_filter(
				'plugin_action_links_' . HTTP_AUTH_BASENAME,
				array( $this, 'settings_link' )
			);
		}

		add_action( HTTP_Auth_Config::is_network_mode() ? 'network_admin_notices' : 'admin_notices', array( $this, 'moved_notice' ) );
		add_action( 'admin_init', array( $this, 'maybe_dismiss_moved_notice' ) );
	}

	/**
	 * Capability required to manage the settings.
	 *
	 * @access public
	 * @since  1.1.0
	 *
	 * @return string
	 */
	public static function get_capability() {
		return HTTP_Auth_Config::is_network_mode() ? 'manage_network_options' : 'activate_plugins';
	}

	/**
	 * URL of a plugin admin page in the network or site admin.
	 *
	 * @access public
	 * @since  1.1.0
	 *
	 * @param string $page Page slug.
	 * @param array  $args Extra query args.
	 *
	 * @return string
	 */
	public static function get_page_url( $page, $args = array() ) {
		$network = HTTP_Auth_Config::is_network_mode();
		if ( 'http-auth-settings' === $page ) {
			$path = ( $network ? 'settings.php' : 'options-general.php' ) . '?page=' . $page;
		} else {
			$path = 'admin.php?page=' . $page;
		}

		$url = $network ? network_admin_url( $path ) : admin_url( $path );

		return add_query_arg( $args, $url );
	}

	/**
	 * Add the settings page under Settings.
	 *
	 * @access public
	 * @since  0.1
	 */
	public function admin_menu() {
		$settings_page = add_submenu_page(
			HTTP_Auth_Config::is_network_mode() ? 'settings.php' : 'options-general.php',
			__( 'HTTP Auth Settings', 'http-auth' ),
			__( 'HTTP Auth', 'http-auth' ),
			self::get_capability(),
			'http-auth-settings',
			array( $this, 'admin_settings_page' )
		);

		add_action( 'load-' . $settings_page, array( $this, 'save_settings' ) );
		add_action(
			'admin_print_styles-' . $settings_page,
			array( $this, 'add_settings_page_style' )
		);
	}

	/**
	 * Get the URL of a plugin stylesheet, unminified when SCRIPT_DEBUG is on.
	 *
	 * @access private
	 * @since  1.1.0
	 *
	 * @param string $name Stylesheet name without extension.
	 *
	 * @return string
	 */
	private function get_style_url( $name ) {
		$suffix = defined( 'SCRIPT_DEBUG' ) && SCRIPT_DEBUG ? '.css' : '.min.css';

		return plugins_url( '/assets/css/' . $name . $suffix, HTTP_AUTH_FILE );
	}

	/**
	 * Add settings page style.
	 *
	 * @access public
	 * @since  1.0.0
	 */
	public function add_settings_page_style() {
		wp_enqueue_style(
			'http-auth-settings-style',
			$this->get_style_url( 'admin-style' ),
			array(),
			HTTP_AUTH_VERSION
		);
	}

	/**
	 * Save settings before the page renders so it can redirect.
	 *
	 * @access public
	 * @since  1.1.0
	 */
	public function save_settings() {
		// The user found the new location.
		HTTP_Auth_Config::delete_moved_notice();

		include_once HTTP_AUTH_PATH . 'admin/class-http-auth-settings.php';
		$settings = new HTTP_Auth_Settings();
		$settings->maybe_save();
	}

	/**
	 * Settings Page from where user can add/change the HTTP Auth credentials,
	 * add custom message and choose where to apply the plugin.
	 *
	 * @access public
	 * @since  0.1
	 */
	public function admin_settings_page() {
		include_once HTTP_AUTH_PATH . 'admin/class-http-auth-settings.php';
		$settings = new HTTP_Auth_Settings();
		$settings->render();

		add_filter( 'admin_footer_text', array( $this, 'admin_footer_text' ), 1 );
	}

	/**
	 * Ask for a review and link to support in the footer of the settings page.
	 *
	 * @access public
	 * @since  0.3
	 *
	 * @return string
	 */
	public function admin_footer_text() {
		$link = '<a href="%s" target="_blank" rel="noopener noreferrer">%s</a>';

		return sprintf(
			// translators: %s is replaced with a five star rating link.
			esc_html__( 'Enjoying HTTP Auth? Please leave a %s rating.', 'http-auth' ),
			sprintf( $link, esc_url( 'https://wordpress.org/support/plugin/http-auth/reviews/?rate=5#new-post' ), '&#9733;&#9733;&#9733;&#9733;&#9733;' )
		) . ' &middot; ' . sprintf( $link, esc_url( 'https://wordpress.org/support/plugin/http-auth/' ), esc_html__( 'Support forum', 'http-auth' ) )
			. ' &middot; ' . sprintf( $link, esc_url( 'https://github.com/samiahmedsiddiqui/http-auth' ), 'GitHub' );
	}

	/**
	 * Tell upgraded sites once that the settings moved under Settings.
	 *
	 * @access public
	 * @since  1.1.0
	 */
	public function moved_notice() {
		$screen = get_current_screen();
		if ( ! HTTP_Auth_Config::has_moved_notice() || ! current_user_can( self::get_capability() )
			|| null === $screen || ! in_array( $screen->base, array( 'dashboard', 'plugins' ), true )
		) {
			return;
		}

		$dismiss = wp_nonce_url( add_query_arg( 'http-auth-dismiss-moved', '1' ), 'http-auth-dismiss-moved' );
		printf(
			'<div class="notice notice-info"><p>%1$s</p><p><a href="%2$s" class="button button-primary">%3$s</a> <a href="%4$s" class="button">%5$s</a></p></div>',
			esc_html__( 'HTTP Auth settings have moved to Settings > HTTP Auth. Your settings were kept.', 'http-auth' ),
			esc_url( self::get_page_url( 'http-auth-settings' ) ),
			esc_html__( 'Open settings', 'http-auth' ),
			esc_url( $dismiss ),
			esc_html__( 'Dismiss', 'http-auth' )
		);
	}

	/**
	 * Dismiss the moved notice.
	 *
	 * @access public
	 * @since  1.1.0
	 */
	public function maybe_dismiss_moved_notice() {
		if ( ! isset( $_GET['http-auth-dismiss-moved'], $_GET['_wpnonce'] )
			|| ! wp_verify_nonce( sanitize_key( wp_unslash( $_GET['_wpnonce'] ) ), 'http-auth-dismiss-moved' )
			|| ! current_user_can( self::get_capability() )
		) {
			return;
		}

		HTTP_Auth_Config::delete_moved_notice();
		wp_safe_redirect( remove_query_arg( array( 'http-auth-dismiss-moved', '_wpnonce' ) ) );
		exit;
	}

	/**
	 * Contact and Settings links under the plugin name on the Plugins page.
	 *
	 * @access public
	 * @since  0.3
	 *
	 * @param array $links Contains the Plugin Basic Links.
	 *
	 * @return array $links Add links in the array and return it.
	 */
	public function settings_link( $links ) {
		$contact = sprintf(
			'<a href="%s" target="_blank">%s</a>',
			esc_url( 'https://www.yasglobal.com/#request-form' ),
			esc_html__( 'Contact', 'http-auth' )
		);

		$settings = sprintf(
			'<a href="%s">%s</a>',
			esc_url( self::get_page_url( 'http-auth-settings' ) ),
			esc_html__( 'Settings', 'http-auth' )
		);

		array_unshift( $links, $settings );
		array_unshift( $links, $contact );

		return $links;
	}
}
