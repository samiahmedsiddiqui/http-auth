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
 * Settings page with a tab for admin and a tab for complete site protection.
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
	 * Get the tab labels in display order.
	 *
	 * @access private
	 * @since  1.1.0
	 *
	 * @return array
	 */
	private function get_tabs() {
		return array(
			'admin' => __( 'Admin Site', 'http-auth' ),
			'site'  => __( 'Complete Site', 'http-auth' ),
		);
	}

	/**
	 * Get the current tab from the request.
	 *
	 * @access private
	 * @since  1.1.0
	 *
	 * @return string
	 */
	private function get_current_tab() {
		// phpcs:disable WordPress.Security.NonceVerification
		$tab = 'admin';
		if ( isset( $_POST['http_auth_tab'] ) ) {
			$tab = sanitize_key( wp_unslash( $_POST['http_auth_tab'] ) );
		} elseif ( isset( $_GET['tab'] ) ) {
			$tab = sanitize_key( wp_unslash( $_GET['tab'] ) );
		}
		// phpcs:enable WordPress.Security.NonceVerification

		return in_array( $tab, HTTP_Auth::TABS, true ) ? $tab : 'admin';
	}

	/**
	 * Generate the tab navigation HTML.
	 *
	 * @access private
	 * @since  1.1.0
	 *
	 * @param string $current  Current tab.
	 * @param array  $settings Settings of all tabs.
	 */
	private function get_tabs_output( $current, $settings ) {
		?>
		<nav class="nav-tab-wrapper http-auth-tabs">
			<?php foreach ( $this->get_tabs() as $tab => $label ) : ?>
				<a href="<?php echo esc_url( HTTP_Auth_Admin::get_page_url( 'http-auth-settings', array( 'tab' => $tab ) ) ); ?>" class="nav-tab<?php echo $current === $tab ? ' nav-tab-active' : ''; ?>">
					<?php echo esc_html( $label ); ?>
					<?php $this->get_badge_output( HTTP_Auth::is_enabled( $settings[ $tab ] ) ); ?>
				</a>
			<?php endforeach; ?>
		</nav>
		<?php
	}

	/**
	 * Generate the Active/Inactive badge HTML.
	 *
	 * @access private
	 * @since  1.1.0
	 *
	 * @param bool $active Whether the tab is active.
	 */
	private function get_badge_output( $active ) {
		if ( $active ) {
			echo '<span class="http-auth-badge is-active">' . esc_html__( 'Active', 'http-auth' ) . '</span>';
		} else {
			echo '<span class="http-auth-badge">' . esc_html__( 'Inactive', 'http-auth' ) . '</span>';
		}
	}

	/**
	 * Generate the Protection card HTML.
	 *
	 * @access private
	 * @since  1.1.0
	 *
	 * @param string $tab      Current tab.
	 * @param string $activate Activation value (`on` or `off`).
	 */
	private function get_protection_output( $tab, $activate ) {
		if ( 'admin' === $tab ) {
			$description = __( 'Protect the login page, admin pages and XML-RPC of the listed sites. Recommended for production and multisite.', 'http-auth' );
		} else {
			$description = __( 'Protect every page of the listed sites. Recommended for development, staging and other environments.', 'http-auth' );
		}
		?>
		<div class="http-auth-card">
			<div class="http-auth-card-header">
				<h2><?php esc_html_e( 'Protection', 'http-auth' ); ?></h2>
				<p><?php echo esc_html( $description ); ?></p>
			</div>
			<div class="http-auth-card-body">
				<label class="http-auth-toggle" for="http-auth-activate">
					<input type="checkbox" id="http-auth-activate" name="http_auth_activate" value="on" <?php checked( 'on', $activate ); ?> />
					<span class="http-auth-toggle-track" aria-hidden="true"></span>
					<span class="http-auth-toggle-label"><?php esc_html_e( 'Enable protection on the listed sites', 'http-auth' ); ?></span>
				</label>
			</div>
		</div>
		<?php
	}

	/**
	 * Generate the Site URLs card HTML.
	 *
	 * @access private
	 * @since  1.1.0
	 *
	 * @param array $urls Site URLs.
	 */
	private function get_urls_output( $urls ) {
		?>
		<div class="http-auth-card">
			<div class="http-auth-card-header">
				<h2><?php esc_html_e( 'Site URLs', 'http-auth' ); ?></h2>
				<p><?php esc_html_e( 'The sites this tab applies to.', 'http-auth' ); ?></p>
			</div>
			<table class="form-table" role="presentation">
				<tbody>
					<tr>
						<th scope="row">
							<label for="http-auth-urls"><?php esc_html_e( 'Apply on', 'http-auth' ); ?></label>
						</th>
						<td>
							<textarea id="http-auth-urls" name="http_auth_urls" rows="5" class="large-text code" placeholder="<?php echo esc_attr( HTTP_Auth::normalize_url( home_url() ) ); ?>" aria-describedby="http-auth-urls-help"><?php echo esc_textarea( implode( "\n", $urls ) ); ?></textarea>
							<p class="description" id="http-auth-urls-help">
								<?php esc_html_e( 'One URL per line, without http(s)://. Use * as a wildcard, e.g. *.example.com', 'http-auth' ); ?>
							</p>
						</td>
					</tr>
				</tbody>
			</table>
		</div>
		<?php
	}

	/**
	 * Generate the Credentials card HTML.
	 *
	 * @access private
	 * @since  1.0.0
	 *
	 * @param string $username     HTTP Auth Username.
	 * @param bool   $has_password Whether a password is already saved.
	 */
	private function get_credentials_output( $username, $has_password ) {
		?>
		<div class="http-auth-card">
			<div class="http-auth-card-header">
				<h2><?php esc_html_e( 'Credentials', 'http-auth' ); ?></h2>
				<p><?php esc_html_e( 'Visitors enter these in the browser login prompt.', 'http-auth' ); ?></p>
			</div>
			<table class="form-table" role="presentation">
				<tbody>
					<tr>
						<th scope="row">
							<label for="http-auth-username"><?php esc_html_e( 'Username', 'http-auth' ); ?></label>
						</th>
						<td>
							<input type="text" id="http-auth-username" name="http_auth_username" value="<?php echo esc_attr( $username ); ?>" class="regular-text" autocomplete="off" required />
						</td>
					</tr>
					<tr>
						<th scope="row">
							<label for="http-auth-password"><?php esc_html_e( 'Password', 'http-auth' ); ?></label>
						</th>
						<td>
							<?php if ( $has_password ) : ?>
								<input type="password" id="http-auth-password" name="http_auth_password" value="" class="regular-text" autocomplete="new-password" aria-describedby="http-auth-password-help" />
								<p class="description" id="http-auth-password-help">
									<?php esc_html_e( 'A password is saved. Leave blank to keep it.', 'http-auth' ); ?>
								</p>
							<?php else : ?>
								<input type="password" id="http-auth-password" name="http_auth_password" value="" class="regular-text" autocomplete="new-password" required />
							<?php endif; ?>
						</td>
					</tr>
				</tbody>
			</table>
		</div>
		<?php
	}

	/**
	 * Generate the Message card HTML.
	 *
	 * @access private
	 * @since  1.0.0
	 *
	 * @param string $message HTTP Auth Cancel message.
	 */
	private function get_message_output( $message ) {
		?>
		<div class="http-auth-card">
			<div class="http-auth-card-header">
				<h2><?php esc_html_e( 'Message', 'http-auth' ); ?> <span class="http-auth-optional"><?php esc_html_e( 'Optional', 'http-auth' ); ?></span></h2>
				<p><?php esc_html_e( 'Shown when the login prompt is cancelled or fails.', 'http-auth' ); ?></p>
			</div>
			<table class="form-table" role="presentation">
				<tbody>
					<tr>
						<th scope="row">
							<label for="http-auth-message"><?php esc_html_e( 'Cancel message', 'http-auth' ); ?></label>
						</th>
						<td>
							<textarea id="http-auth-message" name="http_auth_message" rows="4" class="large-text" placeholder="<?php esc_attr_e( 'This Site is Restricted. Please contact the administrator for access.', 'http-auth' ); ?>"><?php echo esc_textarea( $message ); ?></textarea>
						</td>
					</tr>
				</tbody>
			</table>
		</div>
		<?php
	}

	/**
	 * Generate the sidebar HTML.
	 *
	 * @access private
	 * @since  1.1.0
	 */
	private function get_sidebar_output() {
		if ( HTTP_Auth::is_network_mode() ) {
			$sites_title = __( 'Network sites', 'http-auth' );
			$sites       = array();
			foreach ( get_sites( array( 'number' => 50 ) ) as $site ) {
				$sites[] = HTTP_Auth::normalize_url( $site->domain . $site->path );
			}
		} else {
			$sites_title = __( 'This site', 'http-auth' );
			$sites       = array( HTTP_Auth::normalize_url( home_url() ) );
		}
		?>
		<div class="http-auth-card">
			<div class="http-auth-card-header">
				<h2><?php echo esc_html( $sites_title ); ?></h2>
			</div>
			<div class="http-auth-card-body">
				<ul class="http-auth-sites">
					<?php foreach ( $sites as $site_url ) : ?>
						<li><code><?php echo esc_html( $site_url ); ?></code></li>
					<?php endforeach; ?>
				</ul>
			</div>
		</div>
		<div class="http-auth-card">
			<div class="http-auth-card-header">
				<h2><?php esc_html_e( 'How it works', 'http-auth' ); ?></h2>
			</div>
			<div class="http-auth-card-body">
				<ul class="http-auth-help">
					<li>
						<strong><?php esc_html_e( 'Admin Site', 'http-auth' ); ?></strong>
						<?php esc_html_e( 'protects login, admin pages and XML-RPC.', 'http-auth' ); ?>
					</li>
					<li>
						<strong><?php esc_html_e( 'Complete Site', 'http-auth' ); ?></strong>
						<?php esc_html_e( 'protects every page.', 'http-auth' ); ?>
					</li>
					<li><?php esc_html_e( 'If a site is listed in both tabs, Complete Site is applied.', 'http-auth' ); ?></li>
					<li><?php esc_html_e( 'Each tab has its own credentials.', 'http-auth' ); ?></li>
				</ul>
			</div>
		</div>
		<?php
	}

	/**
	 * Redirect back to the settings tab with a status code.
	 *
	 * @access private
	 * @since  1.1.0
	 *
	 * @param string $tab    Tab to show.
	 * @param string $status Status code shown as a notice.
	 */
	private function redirect( $tab, $status ) {
		wp_safe_redirect(
			HTTP_Auth_Admin::get_page_url(
				'http-auth-settings',
				array(
					'tab'              => $tab,
					'http-auth-status' => $status,
				)
			)
		);
		exit;
	}

	/**
	 * Parse the Site URLs textarea.
	 *
	 * @access private
	 * @since  1.1.0
	 *
	 * @param string $value Textarea value.
	 *
	 * @return array|false Normalized URLs, false if any line is invalid.
	 */
	private function parse_urls( $value ) {
		$urls = array();
		foreach ( preg_split( '/\R/', $value ) as $line ) {
			$url = HTTP_Auth::normalize_url( $line );
			if ( '' === $url ) {
				continue;
			}

			if ( ! preg_match( '#^[a-z0-9*][a-z0-9*.-]*(:\d+)?(/[a-z0-9*._~%/-]*)?$#', $url ) ) {
				return false;
			}

			$urls[] = $url;
		}

		return array_values( array_unique( $urls ) );
	}

	/**
	 * Save the settings of the submitted tab.
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

		if ( ! current_user_can( HTTP_Auth_Admin::get_capability() ) ) {
			wp_die( esc_html__( 'Sorry, you are not allowed to manage these settings.', 'http-auth' ) );
		}

		$tab      = $this->get_current_tab();
		$settings = HTTP_Auth::get_settings();
		$current  = $settings[ $tab ];
		$username = '';
		$password = '';
		$message  = '';
		$urls     = '';

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

		if ( isset( $_POST['http_auth_urls'] ) ) {
			$urls = sanitize_textarea_field( wp_unslash( $_POST['http_auth_urls'] ) );
		}

		$activate = 'off';
		if ( isset( $_POST['http_auth_activate'] ) && 'on' === $_POST['http_auth_activate'] ) {
			$activate = 'on';
		}

		if ( '' === $username || ( '' === $password && '' === $current['password'] ) ) {
			$this->redirect( $tab, 'missing-credentials' );
		}

		// Basic auth sends `username:password` so the username can't have a colon.
		if ( false !== strpos( $username, ':' ) ) {
			$this->redirect( $tab, 'invalid-username' );
		}

		$urls = $this->parse_urls( $urls );
		if ( false === $urls ) {
			$this->redirect( $tab, 'invalid-urls' );
		}

		if ( 'on' === $activate && empty( $urls ) ) {
			$this->redirect( $tab, 'missing-urls' );
		}

		$settings[ $tab ] = array(
			'activate' => $activate,
			'urls'     => $urls,
			'username' => $username,
			'password' => '' === $password ? $current['password'] : wp_hash_password( $password ),
			'message'  => $message,
		);

		HTTP_Auth::update_storage_option( 'http_auth_settings', $settings );

		if ( 'on' === $activate ) {
			HTTP_Auth_Htaccess::add_rules();
		}

		$this->redirect( $tab, 'saved' );
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
			'invalid-urls'        => array( 'error', __( 'One or more site URLs are invalid.', 'http-auth' ) ),
			'missing-urls'        => array( 'error', __( 'Add at least one site URL to activate.', 'http-auth' ) ),
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
		$settings     = HTTP_Auth::get_settings();
		$tab          = $this->get_current_tab();
		$tab_settings = $settings[ $tab ];
		$tabs         = $this->get_tabs();
		?>
		<div class="wrap http-auth-settings">
			<h1><?php esc_html_e( 'HTTP Auth', 'http-auth' ); ?></h1>
			<?php
			$this->get_notice_output();
			$this->get_tabs_output( $tab, $settings );
			?>
			<div class="http-auth-layout">
				<form method="POST" action="" id="http-auth" class="http-auth-main">
					<?php
					wp_nonce_field(
						self::NONCE_ACTION . get_current_user_id(),
						self::NONCE_NAME,
						true
					);
					?>
					<input type="hidden" name="http_auth_tab" value="<?php echo esc_attr( $tab ); ?>" />
					<?php
					$this->get_protection_output( $tab, $tab_settings['activate'] );
					$this->get_urls_output( $tab_settings['urls'] );
					$this->get_credentials_output(
						$tab_settings['username'],
						'' !== $tab_settings['password']
					);
					$this->get_message_output( $tab_settings['message'] );
					?>
					<p class="submit">
						<?php
						submit_button(
							sprintf(
								// translators: %s is replaced with the tab name.
								__( 'Save %s Settings', 'http-auth' ),
								$tabs[ $tab ]
							),
							'primary large',
							'submit',
							false
						);
						?>
					</p>
				</form>
				<aside class="http-auth-sidebar">
					<?php $this->get_sidebar_output(); ?>
				</aside>
			</div>
		</div>
		<?php
	}
}
