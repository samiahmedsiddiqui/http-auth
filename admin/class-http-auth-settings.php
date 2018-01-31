<?php
/**
 * @package HTTPAuth\Admin
 */

class HTTP_Auth_Settings {

	/**
	 * Class constructor.
	 */
	public function __construct() {
		$this->http_auth_configs();
	}

	/**
	 * HTTP Auth Settings
	 */
	private function http_auth_configs() {
		if ( isset( $_POST['submit'] ) ) {
			$activate_auth = '';
			if ( isset( $_POST['http_auth_activate'] ) ) {
				$activate_auth = $_POST['http_auth_activate'];
			}
			$http_settings =  array(
				'username' => esc_attr( $_POST['http_auth_username'] ),
				'password' => esc_attr( $_POST['http_auth_password'] ),
				'message'  => $_POST['http_auth_message'],
				'apply'    => $_POST['http_auth_apply'],
				'activate' => $activate_auth,
			);
			update_option( 'http_auth_settings', serialize( $http_settings ) );

			if ( strtolower( $_SERVER['SERVER_SOFTWARE'] ) == 'apache' ) {
				$filename = ABSPATH . '.htaccess';
				$get_content = file_get_contents( $filename, true );
				if ( $get_content !== false ) {
					if ( strpos( $get_content, '# BEGIN HTTP Auth' ) === false ) {
						$htaccess = fopen( $filename, 'a+' ) or die( 'Unable to open file!' );
						$http_rule  = PHP_EOL;
						$http_rule .= PHP_EOL . '# BEGIN HTTP Auth';
						$http_rule .= PHP_EOL . '<IfModule mod_rewrite.c>';
						$http_rule .= PHP_EOL . 'RewriteEngine on';
						$http_rule .= PHP_EOL . 'RewriteRule .* - [E=HTTP_AUTHORIZATION:%{HTTP:Authorization},L]';
						$http_rule .= PHP_EOL . '</IfModule>';
						$http_rule .= PHP_EOL . '# END HTTP Auth';
						$http_rule .= PHP_EOL;
						fwrite( $htaccess, $http_rule );
						fclose( $htaccess );
					}
				}
			}
		}
		$get_settings = unserialize( get_option('http_auth_settings') );
		$username = $password = $message = $http_activated_checked = '';
		$http_apply_admin = 'checked';
		$http_apply_site  = '';
		if( isset( $get_settings ) && ! empty( $get_settings ) ) {
			$username       = esc_attr( $get_settings['username'] );
			$password       = esc_attr( $get_settings['password'] );
			$message        = $get_settings['message'];
			$applicable     = $get_settings['apply'];
			$auth_activated = $get_settings['activate'];

			if ( $applicable == 'site' ) {
				$http_apply_admin = '';
				$http_apply_site  = 'checked';
			}

			if ( $auth_activated == 'on' ) {
				$http_activated_checked = 'checked';
			}
		}
    wp_enqueue_style( 'style', plugins_url( '/admin/css/admin-style.min.css', HTTP_AUTH_FILE ) );
		?>
		<div class="wrap">
			<h1><?php _e( 'HTTP Auth SETTINGS', 'http-auth' ); ?></h1>
			<form enctype="multipart/form-data" method="POST" action="" id="http-auth">
				<table class="http-auth-table">
					<caption><?php _e( 'Http Credentials', 'http-auth' ); ?></caption>
					<tbody>
						<tr>
							<th><?php _e( 'Username :', 'http-auth' ); ?></th>
							<td><input type="text" name="http_auth_username" value="<?php echo $username; ?>" class="regular-text" required /></td>
						</tr>
						<tr>
							<th><?php _e( 'Password :', 'http-auth' ); ?></th>
							<td><input type="password" name="http_auth_password" value="<?php echo $password; ?>" class="regular-text" required /></td>
						</tr>
					</tbody>
				</table>

				<table class="http-auth-table"> 
					<caption><?php _e( 'Message (Optional)', 'http-auth' ); ?></caption>
					<tbody>
						<tr>
							<th><?php _e( 'Cancel Message :', 'http-auth' ); ?></th>
							<td><textarea name="http_auth_message" rows="5" cols="45"><?php echo $message; ?></textarea></td>
						</tr>
					</tbody>
				</table>

				<table class="http-auth-table http-for">
					<caption><?php _e( 'For', 'http-auth' ); ?></caption>
					<tbody>
						<tr>
							<td><input type="radio" name="http_auth_apply" value="site" <?php echo $http_apply_site; ?> /><strong><?php _e( 'Complete Site', 'http-auth' ); ?></strong></td>
						</tr>
						<tr>
							<td><input type="radio" name="http_auth_apply" value="admin" <?php echo $http_apply_admin ?> /><strong><?php _e( 'Login and Admin Pages', 'http-auth' ); ?></strong></td>
						</tr>
					</tbody>
				</table>

				<table class="http-auth-table">
					<tbody>
						<tr>
							<td><input type="checkbox" name="http_auth_activate" value="on" <?php echo $http_activated_checked; ?> /><strong><?php _e( 'Activate HTTP Authentication', 'http-auth' ); ?></strong></td>
						</tr>
					</tbody>
				</table>
				<p class="submit"><input type="submit" name="submit" id="submit" class="button button-primary" value="<?php _e( 'Save Changes', 'http-auth' ); ?>" /></p>
			</form>
    </div>
    <?php
	}
}
