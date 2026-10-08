<?php
/**
 * HTTP Auth .htaccess handling.
 *
 * @package HTTPAuth
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Manage the .htaccess rule which passes the Authorization header to PHP.
 */
class HTTP_Auth_Htaccess {

	/**
	 * Marker used to wrap the plugin rules inside the .htaccess file.
	 *
	 * @var string
	 */
	const MARKER = 'HTTP Auth';

	/**
	 * Get the .htaccess path and load the required admin helpers.
	 *
	 * @access private
	 * @since  1.1.0
	 *
	 * @return string|false Path of the .htaccess file, false if not on Apache.
	 */
	private static function get_htaccess_file() {
		global $is_apache;

		if ( ! $is_apache ) {
			return false;
		}

		require_once ABSPATH . 'wp-admin/includes/file.php';
		require_once ABSPATH . 'wp-admin/includes/misc.php';

		return get_home_path() . '.htaccess';
	}

	/**
	 * Add (or refresh) the HTTP Auth rules in the .htaccess file.
	 *
	 * @access public
	 * @since  1.1.0
	 *
	 * @return bool True on success, false otherwise.
	 */
	public static function add_rules() {
		$filename = self::get_htaccess_file();
		if ( false === $filename ) {
			return false;
		}

		$rules = array(
			'<IfModule mod_rewrite.c>',
			'RewriteEngine On',
			'RewriteRule .* - [E=HTTP_AUTHORIZATION:%{HTTP:Authorization}]',
			'</IfModule>',
		);

		return insert_with_markers( $filename, self::MARKER, $rules );
	}

	/**
	 * Remove the HTTP Auth rules (including markers) from the .htaccess file.
	 *
	 * @access public
	 * @since  1.1.0
	 *
	 * @return bool True on success or if there was nothing to remove.
	 */
	public static function remove_rules() {
		$filename = self::get_htaccess_file();
		if ( false === $filename || ! file_exists( $filename ) ) {
			return true;
		}

		// phpcs:ignore WordPress.WP.AlternativeFunctions.file_get_contents_file_get_contents
		$content = file_get_contents( $filename );
		if ( false === $content || false === strpos( $content, '# BEGIN ' . self::MARKER ) ) {
			return true;
		}

		if ( ! wp_is_writable( $filename ) ) {
			return false;
		}

		$pattern = '/\R?# BEGIN ' . preg_quote( self::MARKER, '/' ) . '\R.*?# END '
			. preg_quote( self::MARKER, '/' ) . '\R?/s';
		$content = preg_replace( $pattern, PHP_EOL, $content );

		// phpcs:ignore WordPress.WP.AlternativeFunctions.file_system_operations_file_put_contents
		return false !== file_put_contents( $filename, $content, LOCK_EX );
	}
}
