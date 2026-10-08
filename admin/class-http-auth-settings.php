<?php
/**
 * HTTP Auth Settings.
 *
 * @package HTTPAuth
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Settings page, where credentials, message and activation can be set.
 */
class HTTP_Auth_Settings {

	/**
	 * Nonce action prefix.
	 *
	 * @var string
	 */
	const NONCE_ACTION = 'http-auth-settings_';

	/**
	 * Nonce field name.
	 *
	 * @var string
	 */
	const NONCE_NAME = '_http_auth_settings_nonce';

	/**
	 * Generate Credentials section HTML.
	 *
	 * @access private
	 * @since  1.0.0
	 *
	 * @param string $username     HTTP Auth Username.
	 * @param bool   $has_password Whether a password is already saved.
	 */
	private function get_credentials_output( $username, $has_password ) {
		?>
		<table class="http-auth-table">
			<caption>
				<?php esc_html_e( 'Credentials', 'http-auth' ); ?>
			</caption>
			<tbody>
			<tr>
				<th>
					<label for="http-auth-username"><?php esc_html_e( 'Username :', 'http-auth' ); ?></label>
				</th>
				<td>
					<input type="text" id="http-auth-username" name="http_auth_username" value="<?php echo esc_attr( $username ); ?>" class="regular-text" autocomplete="off" required />
				</td>
			</tr>
			<tr>
				<th>
					<label for="http-auth-password"><?php esc_html_e( 'Password :', 'http-auth' ); ?></label>
				</th>
				<td>
					<?php if ( $has_password ) : ?>
						<input type="password" id="http-auth-password" name="http_auth_password" value="" class="regular-text" autocomplete="new-password" placeholder="<?php esc_attr_e( 'Leave blank to keep the current password', 'http-auth' ); ?>" />
					<?php else : ?>
						<input type="password" id="http-auth-password" name="http_auth_password" value="" class="regular-text" autocomplete="new-password" required />
					<?php endif; ?>
				</td>
			</tr>
			</tbody>
		</table>
		<?php
	}

	/**
	 * Generate Message section HTML.
	 *
	 * @access private
	 * @since  1.0.0
	 *
	 * @param string $message HTTP Auth Cancel message.
	 */
	private function get_message_output( $message ) {
		?>
		<table class="http-auth-table">
			<caption>
				<?php esc_html_e( 'Message (Optional)', 'http-auth' ); ?>
			</caption>
			<tbody>
				<tr>
					<th>
						<label for="http-auth-message"><?php esc_html_e( 'Cancel Message :', 'http-auth' ); ?></label>
					</th>
					<td>
						<textarea id="http-auth-message" name="http_auth_message" rows="5" cols="45"><?php echo esc_textarea( $message ); ?></textarea>
					</td>
				</tr>
			</tbody>
		</table>
		<?php
	}

	/**
	 * Generate for section HTML.
	 *
	 * @access private
	 * @since  1.0.0
	 *
	 * @param string $apply Where HTTP Auth is applied (`site` or `admin`).
	 */
	private function get_for_output( $apply ) {
		?>
		<table class="http-auth-table http-for">
			<caption>
				<?php esc_html_e( 'For', 'http-auth' ); ?>
			</caption>
			<tbody>
				<tr>
					<td>
						<label>
							<input type="radio" name="http_auth_apply" value="site" <?php checked( 'site', $apply ); ?> />
							<strong>
								<?php esc_html_e( 'Complete Site', 'http-auth' ); ?>
							</strong>
						</label>
					</td>
				</tr>
				<tr>
					<td>
						<label>
							<input type="radio" name="http_auth_apply" value="admin" <?php checked( 'admin', $apply ); ?> />
							<strong>
								<?php esc_html_e( 'Login, Admin Pages and XML-RPC', 'http-auth' ); ?>
							</strong>
						</label>
					</td>
				</tr>
			</tbody>
		</table>
		<?php
	}

	/**
	 * Redirect back to the settings page with a status code.
	 *
	 * @access private
	 * @since  1.1.0
	 *
	 * @param string $status Status code shown as a notice.
	 */
	private function redirect( $status ) {
		wp_safe_redirect(
			add_query_arg(
				'http-auth-status',
				$status,
				admin_url( 'admin.php?page=http-auth-settings' )
			)
		);
		exit;
	}

	/**
	 * Save HTTP Auth Settings.
	 *
	 * @access public
	 * @since  1.0.0
	 */
	public function maybe_save() {
		if ( ! isset( $_POST[ self::NONCE_NAME ] ) ) {
			return;
		}

		check_admin_referer(
			self::NONCE_ACTION . get_current_user_id(),
			self::NONCE_NAME
		);

		if ( ! current_user_can( 'activate_plugins' ) ) {
			wp_die( esc_html__( 'Sorry, you are not allowed to manage these settings.', 'http-auth' ) );
		}

		$current  = HTTP_Auth::get_settings();
		$username = '';
		$password = '';
		$message  = '';

		// Credentials are stored as typed, the password gets hashed.
		// phpcs:disable WordPress.Security.ValidatedSanitizedInput.InputNotSanitized
		if ( isset( $_POST['http_auth_username'] ) ) {
			$username = trim( wp_unslash( $_POST['http_auth_username'] ) );
		}

		if ( isset( $_POST['http_auth_password'] ) ) {
			$password = trim( wp_unslash( $_POST['http_auth_password'] ) );
		}
		// phpcs:enable WordPress.Security.ValidatedSanitizedInput.InputNotSanitized

		if ( isset( $_POST['http_auth_message'] ) ) {
			$message = sanitize_textarea_field( wp_unslash( $_POST['http_auth_message'] ) );
		}

		if ( '' === $username || ( '' === $password && '' === $current['password'] ) ) {
			$this->redirect( 'missing-credentials' );
		}

		// Basic auth sends `username:password` so the username can't have a colon.
		if ( false !== strpos( $username, ':' ) ) {
			$this->redirect( 'invalid-username' );
		}

		$apply = 'site';
		if ( isset( $_POST['http_auth_apply'] ) && 'admin' === $_POST['http_auth_apply'] ) {
			$apply = 'admin';
		}

		$activate = 'off';
		if ( isset( $_POST['http_auth_activate'] ) && 'on' === $_POST['http_auth_activate'] ) {
			$activate = 'on';
		}

		$settings = array(
			'username' => $username,
			'password' => '' === $password ? $current['password'] : wp_hash_password( $password ),
			'message'  => $message,
			'apply'    => $apply,
			'activate' => $activate,
		);

		update_option( 'http_auth_settings', $settings );

		if ( 'on' === $activate ) {
			HTTP_Auth_Htaccess::add_rules();
		}

		$this->redirect( 'saved' );
	}

	/**
	 * Show the notice for the last save, if any.
	 *
	 * @access private
	 * @since  1.1.0
	 */
	private function get_notice_output() {
		// phpcs:ignore WordPress.Security.NonceVerification.Recommended
		if ( ! isset( $_GET['http-auth-status'] ) ) {
			return;
		}

		$notices = array(
			'saved'               => array( 'success', __( 'Settings saved.', 'http-auth' ) ),
			'missing-credentials' => array( 'error', __( 'Username and password are required.', 'http-auth' ) ),
			'invalid-username'    => array( 'error', __( 'Username can not contain a colon (:).', 'http-auth' ) ),
		);

		// phpcs:ignore WordPress.Security.NonceVerification.Recommended
		$status = sanitize_key( wp_unslash( $_GET['http-auth-status'] ) );
		if ( ! isset( $notices[ $status ] ) ) {
			return;
		}

		printf(
			'<div class="notice notice-%1$s is-dismissible"><p>%2$s</p></div>',
			esc_attr( $notices[ $status ][0] ),
			esc_html( $notices[ $status ][1] )
		);
	}

	/**
	 * HTTP Auth Settings page HTML.
	 *
	 * @access public
	 * @since  0.1
	 */
	public function render() {
		$settings = HTTP_Auth::get_settings();
		?>
		<div class="wrap">
			<h1>
			<?php
			esc_html_e( 'HTTP Auth SETTINGS', 'http-auth' );
			?>
			</h1>
			<?php $this->get_notice_output(); ?>
			<form method="POST" action="" id="http-auth">
			<?php
				wp_nonce_field(
					self::NONCE_ACTION . get_current_user_id(),
					self::NONCE_NAME,
					true
				);

				$this->get_credentials_output(
					$settings['username'],
					'' !== $settings['password']
				);
				$this->get_message_output( $settings['message'] );
				$this->get_for_output( $settings['apply'] );
			?>

			<table class="http-auth-table">
				<tbody>
					<tr>
						<td>
							<label>
								<input type="checkbox" name="http_auth_activate" value="on" <?php checked( 'on', $settings['activate'] ); ?> />
								<strong>
									<?php
									esc_html_e( 'Activate', 'http-auth' );
									?>
								</strong>
							</label>
						</td>
					</tr>
				</tbody>
			</table>
			<p class="submit">
				<input type="submit" name="submit" id="submit" class="button button-primary" value="<?php esc_attr_e( 'Save Changes', 'http-auth' ); ?>" />
			</p>
			</form>
		</div>
		<?php
	}
}
