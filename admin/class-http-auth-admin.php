<?php
/**
 * @package HTTPAuth\Admin
 */

class HTTP_Auth_Admin {

	/**
	 * Class constructor.
	 */
	public function __construct() {
		add_action ( 'admin_menu', array( $this, 'admin_menu' ) );
		add_filter( 'plugin_action_links_' . HTTP_AUTH_BASENAME,
			array( $this, 'settings_link' )
		);
	}

	/**
	 * Add Settings Pages in the Dashboard Menu.
	 */
	public function admin_menu() {
		add_menu_page('HTTP Auth', 'HTTP Auth',
			'administrator', 'http-auth-settings',
			array( $this, 'admin_settings_page' )
		);
		add_submenu_page( 'http-auth-settings', 'HTTP Auth Settings',
			'Settings', 'administrator', 'http-auth-settings',
			array( $this, 'admin_settings_page' )
		);
		add_submenu_page( 'http-auth-settings', 'About HTTP Auth',
			'About', 'administrator', 'http-auth-about-plugins',
			array( $this, 'about_plugin' )
		);
	}

	/**
	 * Admin Settings Page by which you can change the HTTP Auth credentials,
	 * add custom message and choose where to apply the plugin.
	 */
	public function admin_settings_page() {
		if( ! current_user_can('administrator') )  {
			wp_die( 
				__( 'You do not have sufficient permissions to access this page.' )
			);
		}
		require_once(
			HTTP_AUTH_PATH . 'admin/class-http-auth-settings.php'
		);
		new HTTP_Auth_Settings();
		add_filter( 'admin_footer_text', array( $this, 'admin_footer_text' ), 1 );
  }

	/**
	 * Add About Plugins Page
	 */
	public function about_plugin() {
		require_once(
			HTTP_AUTH_PATH . 'admin/class-http-auth-about.php'
		);
		new HTTP_Auth_About();
		add_filter( 'admin_footer_text', array( $this, 'admin_footer_text' ), 1 );
	}

	/**
	 * Add Plugin Support and Follow Message in the footer of Admin Pages
	 */
	public function admin_footer_text() {
		$footer_text = sprintf(
			__( 'HTTP Auth version %s by <a href="%s" title="YAS Global Website" target="_blank">YAS Global</a> - <a href="%s" title="Support forums" target="_blank">Support forums</a> - Follow on Twitter: <a href="%s" title="Follow YAS Global on Twitter" target="_blank">YAS Global</a>', 'http-auth' ),
			HTTP_AUTH_PLUGIN_VERSION, 'https://www.yasglobal.com',
			'https://wordpress.org/support/plugin/http-auth',
			'https://twitter.com/samisiddiqui91'
		);
		return $footer_text;
	}

	/**
	 * Plugin About, Contact and Settings Link on the Plugin Page
	 * under the Plugin Name.
	 */
	public function settings_link( $links ) {
		$about = sprintf(
			__( '<a href="%s" title="About">About</a>', 'http-auth' ),
			'admin.php?page=http-auth-about-plugins'
		);
		$contact = sprintf(
			__( '<a href="%s" title="Contact" target="_blank">Contact</a>', 'http-auth' ),
			'https://www.yasglobal.com/#request-form'
		);
		$settings = sprintf(
			__( '<a href="%s" title="Settings">Settings</a>', 'http-auth' ),
			'admin.php?page=http-auth-settings'
		);
		array_unshift( $links, $settings );
		array_unshift( $links, $contact );
		array_unshift( $links, $about );

		return $links;
	}
}
