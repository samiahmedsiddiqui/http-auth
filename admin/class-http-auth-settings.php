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
 * Settings page with Sites, Admin Site and Complete Site tabs.
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
			'sites' => __( 'Sites', 'http-auth' ),
			'admin' => __( 'Admin Site', 'http-auth' ),
			'site'  => __( 'Complete Site', 'http-auth' ),
		);
	}

	/**
	 * Get the protection mode labels.
	 *
	 * @access private
	 * @since  1.1.0
	 *
	 * @return array
	 */
	private function get_mode_labels() {
		return array(
			'none'  => __( 'None', 'http-auth' ),
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
		$tab = 'sites';
		if ( isset( $_POST['http_auth_tab'] ) ) {
			$tab = sanitize_key( wp_unslash( $_POST['http_auth_tab'] ) );
		} elseif ( isset( $_GET['tab'] ) ) {
			$tab = sanitize_key( wp_unslash( $_GET['tab'] ) );
		}
		// phpcs:enable WordPress.Security.NonceVerification

		return array_key_exists( $tab, $this->get_tabs() ) ? $tab : 'sites';
	}

	/**
	 * Generate the tab navigation HTML.
	 *
	 * @access private
	 * @since  1.1.0
	 *
	 * @param string $current  Current tab.
	 * @param array  $settings Settings.
	 */
	private function get_tabs_output( $current, $settings ) {
		?>
		<nav class="nav-tab-wrapper http-auth-tabs">
			<?php foreach ( $this->get_tabs() as $tab => $label ) : ?>
				<a href="<?php echo esc_url( HTTP_Auth_Admin::get_page_url( 'http-auth-settings', array( 'tab' => $tab ) ) ); ?>" class="nav-tab<?php echo $current === $tab ? ' nav-tab-active' : ''; ?>">
					<?php echo esc_html( $label ); ?>
					<?php
					if ( 'sites' !== $tab ) {
						$this->get_badge_output( HTTP_Auth_Config::is_mode_enabled( $settings['modes'][ $tab ] ) );
					}
					?>
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
	 * @param bool $active Whether the mode is active.
	 */
	private function get_badge_output( $active ) {
		if ( $active ) {
			echo '<span class="http-auth-badge is-active">' . esc_html__( 'Active', 'http-auth' ) . '</span>';
		} else {
			echo '<span class="http-auth-badge">' . esc_html__( 'Inactive', 'http-auth' ) . '</span>';
		}
	}

	/**
	 * Generate a protection mode select.
	 *
	 * @access private
	 * @since  1.1.0
	 *
	 * @param string $name    Field name.
	 * @param string $current Selected mode.
	 * @param array  $modes   Allowed modes.
	 * @param string $label   Accessible label.
	 */
	private function get_mode_select( $name, $current, $modes, $label ) {
		$labels = $this->get_mode_labels();
		?>
		<select name="<?php echo esc_attr( $name ); ?>" aria-label="<?php echo esc_attr( $label ); ?>">
			<?php foreach ( $modes as $mode ) : ?>
				<option value="<?php echo esc_attr( $mode ); ?>" <?php selected( $mode, $current ); ?>><?php echo esc_html( $labels[ $mode ] ); ?></option>
			<?php endforeach; ?>
		</select>
		<?php
	}

	/**
	 * Warn when a row's mode is set but not active.
	 *
	 * @access private
	 * @since  1.1.0
	 *
	 * @param array      $settings Settings.
	 * @param array|null $row      Row, null for the default.
	 */
	private function get_inactive_warning( $settings, $row ) {
		$mode = null === $row ? $settings['default'] : $row['mode'];
		if ( 'none' === $mode || null !== HTTP_Auth_Config::get_row_config( $settings, $row ) ) {
			return;
		}

		$labels = $this->get_mode_labels();
		// translators: %s is replaced with the mode name.
		echo '<p class="http-auth-warning">' . esc_html( sprintf( __( '%s is not active.', 'http-auth' ), $labels[ $mode ] ) ) . '</p>';
	}

	/**
	 * Generate the custom credentials fields of a row.
	 *
	 * @access private
	 * @since  1.1.0
	 *
	 * @param string     $name        Field name prefix.
	 * @param array|null $credentials Custom credentials, null if none.
	 * @param string     $site        Site name for labels.
	 */
	private function get_row_credentials_output( $name, $credentials, $site ) {
		$custom = null !== $credentials;
		$id     = 'http-auth-' . md5( $name );
		?>
		<details class="http-auth-creds">
			<summary>
				<?php
				if ( $custom ) {
					// translators: %s is replaced with the username.
					echo esc_html( sprintf( __( 'Custom credentials: %s', 'http-auth' ), $credentials['username'] ) );
				} else {
					esc_html_e( 'Mode credentials', 'http-auth' );
				}
				?>
			</summary>
			<div class="http-auth-creds-fields">
				<label>
					<input type="checkbox" name="<?php echo esc_attr( $name . '[custom]' ); ?>" value="1" <?php checked( $custom ); ?> />
					<?php
					// translators: %s is replaced with the site URL.
					echo esc_html( sprintf( __( 'Use custom credentials for %s', 'http-auth' ), $site ) );
					?>
				</label>
				<label for="<?php echo esc_attr( $id . '-user' ); ?>"><?php esc_html_e( 'Username', 'http-auth' ); ?></label>
				<input type="text" id="<?php echo esc_attr( $id . '-user' ); ?>" name="<?php echo esc_attr( $name . '[username]' ); ?>" value="<?php echo esc_attr( $custom ? $credentials['username'] : '' ); ?>" autocomplete="off" />
				<label for="<?php echo esc_attr( $id . '-pass' ); ?>"><?php esc_html_e( 'Password', 'http-auth' ); ?></label>
				<input type="password" id="<?php echo esc_attr( $id . '-pass' ); ?>" name="<?php echo esc_attr( $name . '[password]' ); ?>" value="" autocomplete="new-password" placeholder="<?php echo $custom ? esc_attr__( 'Leave blank to keep it', 'http-auth' ) : ''; ?>" />
				<label for="<?php echo esc_attr( $id . '-msg' ); ?>"><?php esc_html_e( 'Message (optional)', 'http-auth' ); ?></label>
				<textarea id="<?php echo esc_attr( $id . '-msg' ); ?>" name="<?php echo esc_attr( $name . '[message]' ); ?>" rows="2"><?php echo esc_textarea( $custom ? $credentials['message'] : '' ); ?></textarea>
			</div>
		</details>
		<?php
	}

	/**
	 * Generate the Sites tab HTML.
	 *
	 * @access private
	 * @since  1.1.0
	 *
	 * @param array $settings Settings.
	 */
	private function get_sites_output( $settings ) {
		$rows    = HTTP_Auth_Config::get_sites( $settings );
		$matched = HTTP_Auth_Config::match_site( $rows );
		$labels  = $this->get_mode_labels();
		$index   = 0;
		?>
		<div class="http-auth-card">
			<div class="http-auth-card-header">
				<h2><?php esc_html_e( 'Sites', 'http-auth' ); ?></h2>
				<p><?php esc_html_e( 'Choose how each site is protected. A site matches its exact URL first, then the most specific wildcard.', 'http-auth' ); ?></p>
			</div>
			<table class="widefat striped http-auth-sites-table">
				<thead>
					<tr>
						<th scope="col"><?php esc_html_e( 'Site URL', 'http-auth' ); ?></th>
						<th scope="col" class="http-auth-col-source"><?php esc_html_e( 'Source', 'http-auth' ); ?></th>
						<th scope="col" class="http-auth-col-mode"><?php esc_html_e( 'Protection', 'http-auth' ); ?></th>
						<th scope="col" class="http-auth-col-remove"><?php esc_html_e( 'Remove', 'http-auth' ); ?></th>
					</tr>
				</thead>
				<tbody>
					<?php if ( empty( $rows ) ) : ?>
						<tr>
							<td colspan="4"><?php esc_html_e( 'No sites yet. Add them below or in code.', 'http-auth' ); ?></td>
						</tr>
					<?php endif; ?>
					<?php foreach ( $rows as $row ) : ?>
						<?php
						$name       = 'http_auth_rows[' . $index . ']';
						$is_current = null !== $matched && $matched['url'] === $row['url'];
						?>
						<tr<?php echo $is_current ? ' class="is-current"' : ''; ?>>
							<td>
								<input type="hidden" name="<?php echo esc_attr( $name . '[url]' ); ?>" value="<?php echo esc_attr( $row['url'] ); ?>" />
								<code><?php echo esc_html( $row['url'] ); ?></code>
								<?php if ( $is_current ) : ?>
									<span class="http-auth-badge is-current"><?php esc_html_e( 'This site', 'http-auth' ); ?></span>
								<?php endif; ?>
							</td>
							<td>
								<span class="http-auth-source"><?php echo 'code' === $row['source'] ? esc_html__( 'Code', 'http-auth' ) : esc_html__( 'Settings', 'http-auth' ); ?></span>
							</td>
							<td>
								<?php
								$this->get_mode_select(
									$name . '[mode]',
									$row['mode'],
									array( 'none', 'admin', 'site' ),
									// translators: %s is replaced with the site URL.
									sprintf( __( 'Protection for %s', 'http-auth' ), $row['url'] )
								);
								$this->get_inactive_warning( $settings, $row );
								$this->get_row_credentials_output(
									$name,
									isset( $row['credentials'] ) ? $row['credentials'] : null,
									$row['url']
								);
								?>
							</td>
							<td class="http-auth-col-remove">
								<?php if ( 'settings' === $row['source'] ) : ?>
									<?php
									// translators: %s is replaced with the site URL.
									$remove_label = sprintf( __( 'Remove %s', 'http-auth' ), $row['url'] );
									?>
									<input type="checkbox" name="<?php echo esc_attr( $name . '[remove]' ); ?>" value="1" aria-label="<?php echo esc_attr( $remove_label ); ?>" />
								<?php endif; ?>
							</td>
						</tr>
						<?php ++$index; ?>
					<?php endforeach; ?>
					<tr class="http-auth-default-row<?php echo null === $matched ? ' is-current' : ''; ?>">
						<td>
							<strong><?php esc_html_e( 'Any other site', 'http-auth' ); ?></strong>
							<?php if ( null === $matched ) : ?>
								<span class="http-auth-badge is-current"><?php esc_html_e( 'This site', 'http-auth' ); ?></span>
							<?php endif; ?>
						</td>
						<td><span class="http-auth-source"><?php esc_html_e( 'Default', 'http-auth' ); ?></span></td>
						<td>
							<?php
							$this->get_mode_select( 'http_auth_default', $settings['default'], array( 'none', 'admin' ), __( 'Protection for any other site', 'http-auth' ) );
							$this->get_inactive_warning( $settings, null );
							$this->get_row_credentials_output( 'http_auth_default_creds', $settings['default_credentials'], __( 'any other site', 'http-auth' ) );
							?>
						</td>
						<td class="http-auth-col-remove"></td>
					</tr>
				</tbody>
			</table>
		</div>

		<div class="http-auth-card">
			<div class="http-auth-card-header">
				<h2><?php esc_html_e( 'Add sites', 'http-auth' ); ?></h2>
				<p><?php esc_html_e( 'Stored so environment pushes on hosts like WP Engine, Kinsta and Pantheon can not rewrite them.', 'http-auth' ); ?></p>
			</div>
			<table class="form-table" role="presentation">
				<tbody>
					<tr>
						<th scope="row">
							<label for="http-auth-new-urls"><?php esc_html_e( 'Site URLs', 'http-auth' ); ?></label>
						</th>
						<td>
							<textarea id="http-auth-new-urls" name="http_auth_new_urls" rows="3" class="large-text code" placeholder="staging.example.com" aria-describedby="http-auth-new-urls-help"></textarea>
							<p class="description" id="http-auth-new-urls-help">
								<?php esc_html_e( 'One URL per line, without http(s)://. Use * as a wildcard, e.g. *.example.com', 'http-auth' ); ?>
							</p>
						</td>
					</tr>
					<tr>
						<th scope="row"><?php esc_html_e( 'Protection', 'http-auth' ); ?></th>
						<td>
							<?php $this->get_mode_select( 'http_auth_new_mode', 'site', array( 'none', 'admin', 'site' ), __( 'Protection for the new sites', 'http-auth' ) ); ?>
						</td>
					</tr>
				</tbody>
			</table>
		</div>

		<div class="http-auth-card">
			<div class="http-auth-card-header">
				<h2><?php esc_html_e( 'Define sites in code', 'http-auth' ); ?></h2>
				<p><?php esc_html_e( 'List every environment in wp-config.php, a plugin or functions.php. Code is never rewritten by a push.', 'http-auth' ); ?></p>
			</div>
			<div class="http-auth-card-body">
				<pre class="http-auth-code">define( '<?php echo esc_html( HTTP_Auth_Config::URLS_CONSTANT ); ?>', array( 'example.com', 'staging.example.com' ) );</pre>
				<p class="description">
					<?php
					printf(
						// translators: %s is replaced with the filter name.
						esc_html__( 'Or use the %s filter. Choose the protection for each URL above.', 'http-auth' ),
						'<code>http_auth_site_urls</code>'
					);
					?>
				</p>
			</div>
		</div>
		<?php
	}

	/**
	 * Generate the Protection card HTML of a mode.
	 *
	 * @access private
	 * @since  1.1.0
	 *
	 * @param string $mode     Mode.
	 * @param string $activate Activation value (`on` or `off`).
	 */
	private function get_protection_output( $mode, $activate ) {
		if ( 'admin' === $mode ) {
			$description = __( 'Protects the login page, admin pages and XML-RPC of the sites set to Admin Site. Recommended for production and multisite.', 'http-auth' );
		} else {
			$description = __( 'Protects every page of the sites set to Complete Site. Recommended for development, staging and other environments.', 'http-auth' );
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
					<span class="http-auth-toggle-label"><?php esc_html_e( 'Enable protection', 'http-auth' ); ?></span>
				</label>
			</div>
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
	 *
	 * @param string $tab      Current tab.
	 * @param array  $settings Settings.
	 */
	private function get_sidebar_output( $tab, $settings ) {
		$labels = $this->get_mode_labels();
		$mode   = HTTP_Auth_Config::get_current_mode( $settings );
		$config = HTTP_Auth_Config::get_row_config( $settings, HTTP_Auth_Config::match_site( HTTP_Auth_Config::get_sites( $settings ) ) );
		$status = $labels[ $mode ];
		if ( 'none' !== $mode && null === $config ) {
			// translators: %s is replaced with the mode name.
			$status = sprintf( __( '%s (not active)', 'http-auth' ), $labels[ $mode ] );
		} elseif ( null !== $config && $config['custom'] ) {
			// translators: %s is replaced with the mode name.
			$status = sprintf( __( '%s with custom credentials', 'http-auth' ), $labels[ $mode ] );
		}
		?>
		<div class="http-auth-card">
			<div class="http-auth-card-header">
				<h2><?php esc_html_e( 'This site', 'http-auth' ); ?></h2>
			</div>
			<div class="http-auth-card-body">
				<p><code><?php echo esc_html( HTTP_Auth_Config::normalize_url( home_url() ) ); ?></code></p>
				<p>
					<?php
					// translators: %s is replaced with the protection mode.
					echo esc_html( sprintf( __( 'Protection: %s', 'http-auth' ), $status ) );
					?>
				</p>
			</div>
		</div>
		<?php
		if ( 'sites' !== $tab ) {
			$this->get_mode_sites_output( $tab, $settings );
		}
		?>
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
					<li><?php esc_html_e( 'Each mode has its own credentials. A site can use custom credentials instead.', 'http-auth' ); ?></li>
					<li><?php esc_html_e( 'Sites not listed use the default: None or Admin Site.', 'http-auth' ); ?></li>
				</ul>
			</div>
		</div>
		<?php
		$this->get_support_output();
	}

	/**
	 * Generate the Support card HTML.
	 *
	 * @access private
	 * @since  1.1.0
	 */
	private function get_support_output() {
		$links = array(
			'https://wordpress.org/support/plugin/http-auth/'               => __( 'Support forum', 'http-auth' ),
			'https://wordpress.org/support/plugin/http-auth/reviews/?rate=5#new-post' => __( 'Rate HTTP Auth', 'http-auth' ),
			'https://github.com/samiahmedsiddiqui/http-auth'                => __( 'GitHub', 'http-auth' ),
			'https://profiles.wordpress.org/sasiddiqui/#content-plugins'     => __( 'More plugins by Sami', 'http-auth' ),
		);
		?>
		<div class="http-auth-card">
			<div class="http-auth-card-header">
				<h2><?php esc_html_e( 'Support', 'http-auth' ); ?></h2>
			</div>
			<div class="http-auth-card-body">
				<p>
					<?php
					// translators: %s is replaced with the plugin version.
					echo esc_html( sprintf( __( 'Version %s', 'http-auth' ), HTTP_AUTH_VERSION ) );
					?>
				</p>
				<ul class="http-auth-links">
					<?php foreach ( $links as $url => $label ) : ?>
						<li><a href="<?php echo esc_url( $url ); ?>" target="_blank" rel="noopener noreferrer"><?php echo esc_html( $label ); ?></a></li>
					<?php endforeach; ?>
				</ul>
			</div>
		</div>
		<?php
	}

	/**
	 * Generate the list of sites using a mode.
	 *
	 * @access private
	 * @since  1.1.0
	 *
	 * @param string $mode     Mode.
	 * @param array  $settings Settings.
	 */
	private function get_mode_sites_output( $mode, $settings ) {
		$labels = $this->get_mode_labels();
		$using  = array();
		foreach ( HTTP_Auth_Config::get_sites( $settings ) as $row ) {
			if ( $mode === $row['mode'] ) {
				$using[] = $row['url'];
			}
		}

		$uses_default = 'admin' === $mode && 'admin' === $settings['default'];
		?>
		<div class="http-auth-card">
			<div class="http-auth-card-header">
				<h2>
					<?php
					// translators: %s is replaced with the mode name.
					echo esc_html( sprintf( __( 'Sites using %s', 'http-auth' ), $labels[ $mode ] ) );
					?>
				</h2>
			</div>
			<div class="http-auth-card-body">
				<?php if ( empty( $using ) && ! $uses_default ) : ?>
					<p><?php esc_html_e( 'None yet. Assign sites in the Sites tab.', 'http-auth' ); ?></p>
				<?php else : ?>
					<ul class="http-auth-sites">
						<?php foreach ( $using as $url ) : ?>
							<li><code><?php echo esc_html( $url ); ?></code></li>
						<?php endforeach; ?>
						<?php if ( $uses_default ) : ?>
							<li><?php esc_html_e( 'Any other site', 'http-auth' ); ?></li>
						<?php endif; ?>
					</ul>
				<?php endif; ?>
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
	 * Parse a textarea of URLs.
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
			$url = HTTP_Auth_Config::normalize_url( $line );
			if ( '' === $url ) {
				continue;
			}

			if ( ! HTTP_Auth_Config::is_valid_url( $url ) ) {
				return false;
			}

			$urls[] = $url;
		}

		return array_values( array_unique( $urls ) );
	}

	/**
	 * Save the submitted tab.
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

		$tab = $this->get_current_tab();
		if ( 'sites' === $tab ) {
			$this->save_sites();
		} else {
			$this->save_mode( $tab );
		}
	}

	/**
	 * Save the Sites tab.
	 *
	 * @access private
	 * @since  1.1.0
	 */
	private function save_sites() {
		// phpcs:disable WordPress.Security.NonceVerification.Missing -- Verified in maybe_save().
		$settings  = HTTP_Auth_Config::get_settings();
		$code_urls = HTTP_Auth_Config::get_code_urls();
		$modes     = array( 'none', 'admin', 'site' );
		$rows      = array();

		// Fields are sanitized one by one below, passwords are kept as typed.
		$posted = array();
		if ( isset( $_POST['http_auth_rows'] ) && is_array( $_POST['http_auth_rows'] ) ) {
			// phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotSanitized
			$posted = wp_unslash( $_POST['http_auth_rows'] );
		}

		foreach ( $posted as $item ) {
			if ( ! is_array( $item ) || ! isset( $item['url'] ) || ! is_string( $item['url'] ) ) {
				continue;
			}

			$url     = HTTP_Auth_Config::normalize_url( sanitize_text_field( $item['url'] ) );
			$is_code = in_array( $url, $code_urls, true );
			if ( ! HTTP_Auth_Config::is_valid_url( $url ) || isset( $rows[ $url ] )
				|| ( ! $is_code && ! empty( $item['remove'] ) )
			) {
				continue;
			}

			$mode = isset( $item['mode'] ) && is_string( $item['mode'] ) ? sanitize_key( $item['mode'] ) : '';

			$rows[ $url ] = array(
				'url'    => $url,
				'mode'   => in_array( $mode, $modes, true ) ? $mode : 'none',
				'source' => $is_code ? 'code' : 'settings',
			);

			$existing    = isset( $settings['sites'][ $url ]['credentials'] ) ? $settings['sites'][ $url ]['credentials'] : null;
			$credentials = $this->get_posted_credentials( $item, $existing );
			if ( is_string( $credentials ) ) {
				$this->redirect( 'sites', $credentials );
			}

			if ( null !== $credentials ) {
				$rows[ $url ]['credentials'] = $credentials;
			}
		}

		$default_item = array();
		if ( isset( $_POST['http_auth_default_creds'] ) && is_array( $_POST['http_auth_default_creds'] ) ) {
			// phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotSanitized
			$default_item = wp_unslash( $_POST['http_auth_default_creds'] );
		}

		$default_credentials = $this->get_posted_credentials( $default_item, $settings['default_credentials'] );
		if ( is_string( $default_credentials ) ) {
			$this->redirect( 'sites', $default_credentials );
		}

		$new_urls = '';
		if ( isset( $_POST['http_auth_new_urls'] ) ) {
			$new_urls = sanitize_textarea_field( wp_unslash( $_POST['http_auth_new_urls'] ) );
		}

		$new_urls = $this->parse_urls( $new_urls );
		if ( false === $new_urls ) {
			$this->redirect( 'sites', 'invalid-urls' );
		}

		$new_mode = 'none';
		if ( isset( $_POST['http_auth_new_mode'] ) ) {
			$posted_mode = sanitize_key( wp_unslash( $_POST['http_auth_new_mode'] ) );
			if ( in_array( $posted_mode, $modes, true ) ) {
				$new_mode = $posted_mode;
			}
		}

		foreach ( $new_urls as $url ) {
			if ( ! isset( $rows[ $url ] ) ) {
				$rows[ $url ] = array(
					'url'    => $url,
					'mode'   => $new_mode,
					'source' => in_array( $url, $code_urls, true ) ? 'code' : 'settings',
				);
			}
		}

		$settings['sites']               = $rows;
		$settings['default_credentials'] = $default_credentials;
		$settings['default']             = isset( $_POST['http_auth_default'] ) && 'admin' === $_POST['http_auth_default'] ? 'admin' : 'none';
		// phpcs:enable WordPress.Security.NonceVerification.Missing

		HTTP_Auth_Config::save_settings( $settings );

		$this->redirect( 'sites', 'saved' );
	}

	/**
	 * Get custom credentials posted for a row.
	 *
	 * @access private
	 * @since  1.1.0
	 *
	 * @param array      $item     Posted row fields, unslashed.
	 * @param array|null $existing Saved custom credentials, null if none.
	 *
	 * @return array|string|null Credentials, an error code, or null if not custom.
	 */
	private function get_posted_credentials( $item, $existing ) {
		if ( empty( $item['custom'] ) ) {
			return null;
		}

		$username = isset( $item['username'] ) && is_string( $item['username'] ) ? trim( $item['username'] ) : '';
		$password = isset( $item['password'] ) && is_string( $item['password'] ) ? trim( $item['password'] ) : '';
		$message  = isset( $item['message'] ) && is_string( $item['message'] ) ? sanitize_textarea_field( $item['message'] ) : '';

		if ( '' === $username || ( '' === $password && null === $existing ) ) {
			return 'missing-custom-credentials';
		}

		// Basic auth sends `username:password` so the username can't have a colon.
		if ( false !== strpos( $username, ':' ) ) {
			return 'invalid-username';
		}

		return array(
			'username' => $username,
			'password' => '' === $password ? $existing['password'] : wp_hash_password( $password ),
			'message'  => $message,
		);
	}

	/**
	 * Save the credentials, message and activation of a mode.
	 *
	 * @access private
	 * @since  1.1.0
	 *
	 * @param string $mode Mode.
	 */
	private function save_mode( $mode ) {
		// phpcs:disable WordPress.Security.NonceVerification.Missing -- Verified in maybe_save().
		$settings = HTTP_Auth_Config::get_settings();
		$current  = $settings['modes'][ $mode ];
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

		$activate = 'off';
		if ( isset( $_POST['http_auth_activate'] ) && 'on' === $_POST['http_auth_activate'] ) {
			$activate = 'on';
		}
		// phpcs:enable WordPress.Security.NonceVerification.Missing

		if ( '' === $username || ( '' === $password && '' === $current['password'] ) ) {
			$this->redirect( $mode, 'missing-credentials' );
		}

		// Basic auth sends `username:password` so the username can't have a colon.
		if ( false !== strpos( $username, ':' ) ) {
			$this->redirect( $mode, 'invalid-username' );
		}

		$settings['modes'][ $mode ] = array(
			'activate' => $activate,
			'username' => $username,
			'password' => '' === $password ? $current['password'] : wp_hash_password( $password ),
			'message'  => $message,
		);

		HTTP_Auth_Config::save_settings( $settings );

		if ( 'on' === $activate ) {
			HTTP_Auth_Htaccess::add_rules();
		}

		$this->redirect( $mode, 'saved' );
	}

	/**
	 * Show the notices for the page.
	 *
	 * @access private
	 * @since  1.1.0
	 */
	private function get_notice_output() {
		if ( HTTP_Auth_Config::is_disabled() ) {
			printf(
				'<div class="notice notice-warning"><p>%s</p></div>',
				sprintf(
					// translators: %s is replaced with the constant name.
					esc_html__( 'HTTP Auth is turned off by %s in wp-config.php. Remove it to turn protection back on.', 'http-auth' ),
					'<code>HTTP_AUTH_DISABLE</code>'
				)
			);
		}

		// phpcs:ignore WordPress.Security.NonceVerification.Recommended
		if ( ! isset( $_GET['http-auth-status'] ) ) {
			return;
		}

		$notices = array(
			'saved'                      => array( 'success', __( 'Settings saved.', 'http-auth' ) ),
			'missing-credentials'        => array( 'error', __( 'Username and password are required.', 'http-auth' ) ),
			'invalid-username'           => array( 'error', __( 'Username can not contain a colon (:).', 'http-auth' ) ),
			'invalid-urls'               => array( 'error', __( 'One or more site URLs are invalid.', 'http-auth' ) ),
			'missing-custom-credentials' => array( 'error', __( 'Custom credentials need a username and password.', 'http-auth' ) ),
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
		$settings = HTTP_Auth_Config::get_settings();
		$tab      = $this->get_current_tab();
		$tabs     = $this->get_tabs();
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
					if ( 'sites' === $tab ) {
						$this->get_sites_output( $settings );
					} else {
						$mode_settings = $settings['modes'][ $tab ];
						$this->get_protection_output( $tab, $mode_settings['activate'] );
						$this->get_credentials_output(
							$mode_settings['username'],
							'' !== $mode_settings['password']
						);
						$this->get_message_output( $mode_settings['message'] );
					}
					?>
					<p class="submit">
						<?php
						submit_button(
							sprintf(
								// translators: %s is replaced with the tab name.
								__( 'Save %s', 'http-auth' ),
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
					<?php $this->get_sidebar_output( $tab, $settings ); ?>
				</aside>
			</div>
		</div>
		<?php
	}
}
