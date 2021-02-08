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
	 * Css file suffix (version number with with extension)
	 *
	 * @var string
	 */
	private $css_suffix = '-' . HTTP_AUTH_VERSION . '.min.css';

	/**
	 * Initializes WordPress hooks.
	 */
	public function __construct() {
		add_action( 'admin_menu', array( $this, 'admin_menu' ) );
		add_filter(
			'plugin_action_links_' . HTTP_AUTH_BASENAME,
			array( $this, 'settings_link' )
		);
	}

	/**
	 * Added Pages in Dashboard Menu for Settings.
	 *
	 * @access public
	 * @since  0.1
	 */
	public function admin_menu() {
		add_menu_page(
			'HTTP Auth',
			'HTTP Auth',
			'administrator',
			'http-auth-settings',
			array( $this, 'admin_settings_page' )
		);
		$settings_page = add_submenu_page(
			'http-auth-settings',
			'HTTP Auth Settings',
			'Settings',
			'administrator',
			'http-auth-settings',
			array( $this, 'admin_settings_page' )
		);
		$about_page    = add_submenu_page(
			'http-auth-settings',
			'About HTTP Auth',
			'About',
			'administrator',
			'http-auth-about-plugins',
			array( $this, 'about_plugin' )
		);

		add_action(
			'admin_print_styles-' . $settings_page . '',
			array( $this, 'add_settings_page_style' )
		);
		add_action(
			'admin_print_styles-' . $about_page . '',
			array( $this, 'add_about_style' )
		);
	}

	/**
	 * Add about page style.
	 *
	 * @access public
	 * @since  1.0.0
	 */
	public function add_about_style() {
		$filename   = 'about-plugins' . $this->css_suffix;
		$plugin_url = plugins_url( '/admin', HTTP_AUTH_FILE );
		wp_enqueue_style(
			'http-auth-about-style',
			$plugin_url . '/css/' . $filename
		);
	}

	/**
	 * Add settings page style.
	 *
	 * @access public
	 * @since  1.0.0
	 */
	public function add_settings_page_style() {
		$filename   = 'admin-style' . $this->css_suffix;
		$plugin_url = plugins_url( '/admin', HTTP_AUTH_FILE );
		wp_enqueue_style(
			'http-auth-settings-style',
			$plugin_url . '/css/' . $filename
		);
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
		new HTTP_Auth_Settings();

		add_filter( 'admin_footer_text', array( $this, 'admin_footer_text' ), 1 );
	}

	/**
	 * Add About Plugins Page
	 *
	 * @access public
	 * @since  0.3
	 */
	public function about_plugin() {
		include_once HTTP_AUTH_PATH . 'admin/class-http-auth-about.php';
		new HTTP_Auth_About();

		add_filter( 'admin_footer_text', array( $this, 'admin_footer_text' ), 1 );
	}

	/**
	 * Add Plugin Support and Follow Message in the footer of Admin Pages.
	 *
	 * @access public
	 * @since  0.3
	 *
	 * @return string
	 */
	public function admin_footer_text() {
		$footer_text = sprintf(
			/*
			 * translators: Do not translate placeholders, they are replaced with the
			 * appropriate links and plugin version.
			 */
			__(
				'HTTP Auth version %1$s by <a href="%2$s" title="YAS Global Website" target="_blank">YAS Global</a> - <a href="%3$s" title="Support forums" target="_blank">Support forums</a> - Follow on Twitter: <a href="%4$s" title="Follow YAS Global on Twitter" target="_blank">YAS Global</a>',
				'http-auth'
			),
			HTTP_AUTH_VERSION,
			'https://www.yasglobal.com',
			'https://wordpress.org/support/plugin/http-auth',
			'https://twitter.com/samisiddiqui91'
		);

		return $footer_text;
	}

	/**
	 * Plugin About, Contact and Settings Link on the Plugin Page under
	 * the Plugin Name.
	 *
	 * @access public
	 * @since  0.3
	 *
	 * @param array $links Contains the Plugin Basic Link (Activate/Deactivate/Delete)
	 *
	 * @return array $links Add links in the array and return it.
	 */
	public function settings_link( $links ) {
		$about = sprintf(
			// translators: Placeholder replaced with the `About` page link.
			__( '<a href="%s" title="About">About</a>', 'http-auth' ),
			'admin.php?page=http-auth-about-plugins'
		);

		$contact = sprintf(
			// translators: Placeholder replaced with the external `Contact` page link.
			__(
				'<a href="%s" title="Contact" target="_blank">Contact</a>',
				'http-auth'
			),
			'https://www.yasglobal.com/#request-form'
		);

		$settings = sprintf(
			// translators: Placeholder replaced with the `Settings` page link.
			__( '<a href="%s" title="Settings">Settings</a>', 'http-auth' ),
			'admin.php?page=http-auth-settings'
		);

		array_unshift( $links, $settings );
		array_unshift( $links, $contact );
		array_unshift( $links, $about );

		return $links;
	}
}
