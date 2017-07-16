<?php

/**
 * @package http_auth
 */

class Http_Auth_Admin {
  
  private static $initiated = false;

  /**
	 * Initializes WordPress hooks
	 */
  public static function init() {
    if ( ! self::$initiated ) {
			self::$initiated = true;

      add_action( 'admin_menu', array('Http_Auth_Admin', 'http_auth_menu') );
		}
  }

  public static function http_auth_menu() {
	  add_menu_page('HTTP AUTH SETTINGS', 'HTTP AUTH Settings', 'administrator', 'http-auth-settings', array('Http_Auth_Admin', 'http_auth_settings_page'));
    add_action( 'admin_init', array('Http_Auth_Admin', 'http_auth_settings') );
  }

  public static function http_auth_settings() {
    register_setting( 'http-auth-settings-group', 'http_auth_username' );
    register_setting( 'http-auth-settings-group', 'http_auth_password' );
    register_setting( 'http-auth-settings-group', 'http_auth_message' );
    register_setting( 'http-auth-settings-group', 'http_auth_apply' );
    register_setting( 'http-auth-settings-group', 'http_auth_activate' );
  }

  public static function http_auth_settings_page() {
    echo '<div class="wrap">';
    echo '<h2>HTTP Auth SETTINGS</h2>';
    echo '<form method="post" action="options.php">';
    settings_fields( 'http-auth-settings-group' );
    do_settings_sections( 'http-auth-settings-group' );
    $http_activated = esc_attr( get_option('http_auth_activate') );
    $http_activated_value = "on";
    $http_activated_checked = "";
    if($http_activated == "on") {
      $http_activated_checked = "checked";
    }
    $http_apply = esc_attr( get_option('http_auth_apply') );
    $http_apply_admin = "checked";
    $http_apply_site = "";
    if($http_apply == "site"){
      $http_apply_admin = "";
      $http_apply_site = "checked";
    }
    wp_enqueue_style( 'style', plugins_url('/css/admin-style.min.css', __FILE__) );
    ?>
    <table class="http-auth-table">
      <caption>Http Credentials</caption>
      <tbody>
        <tr>
          <th>Username :</th>
          <td><input type="text" name="http_auth_username" value="<?php echo esc_attr( get_option('http_auth_username') ); ?>" class="regular-text" required /></td>
        </tr>
        <tr>
          <th>Password :</th>
          <td><input type="password" name="http_auth_password" value="<?php echo esc_attr( get_option('http_auth_password') ); ?>" class="regular-text" required /></td>
        </tr>
      </tbody>
    </table>

    <table class="http-auth-table"> 
      <caption>Message (Optional)</caption>
      <tbody>
        <tr>
          <th>Cancel Message :</th>
          <td><textarea name="http_auth_message" rows="5" cols="45"><?php echo esc_attr( get_option('http_auth_message') ); ?></textarea></td>
        </tr>
      </tbody>
    </table>

    <table class="http-auth-table http-for">
      <caption>For</caption>
      <tbody>
        <tr>
          <td><input type="radio" name="http_auth_apply" value="site" <?php echo $http_apply_site; ?> /><strong>Complete Site</strong></td>
        </tr>
        <tr>
          <td><input type="radio" name="http_auth_apply" value="admin" <?php echo $http_apply_admin ?> /><strong>Login and Admin Pages</strong></td>
        </tr>
      </tbody>
    </table>

    <table class="http-auth-table">
      <tbody>
        <tr>
          <td><input type="checkbox" name="http_auth_activate" value="<?php echo $http_activated_value; ?>" <?php echo $http_activated_checked; ?> /><strong>Activate HTTP Authentication</strong></td>
        </tr>
      </tbody>
    </table>

    <?php
    submit_button(); 
    echo '</form>';
    echo '</div>';
  }
}
