<?php
/*
Plugin Name: Http Auth
Plugin URI: https://wordpress.org/plugins/http-auth/
Description: This plugin allows you apply HTTP Auth on your site. You can apply Http Authentication all over the site or only the admin pages.
Version:     0.1
Donate link: https://www.paypal.me/yasglobal
Author: Sami Ahmed Siddiqui
Author URI: http://www.yasglobal.com/web-design-development/wordpress/http-auth/
Text Domain: http-auth
*/

function http_auth_settings_link($links) { 
  $settings_link = '<a href="admin.php?page=http-auth-settings">Settings</a>'; 
  array_unshift($links, $settings_link); 
  return $links; 
}

function http_auth_menu() {
	add_menu_page('HTTP AUTH SETTINGS', 'HTTP AUTH Settings', 'administrator', 'http-auth-settings', 'http_auth_settings_page');
  add_action( 'admin_init', 'http_auth_settings' );
}

function http_auth_settings() {
   register_setting( 'http-auth-settings-group', 'http_auth_username' );
   register_setting( 'http-auth-settings-group', 'http_auth_password' );
   register_setting( 'http-auth-settings-group', 'http_auth_message' );
   register_setting( 'http-auth-settings-group', 'http_auth_apply' );
   register_setting( 'http-auth-settings-group', 'http_auth_activate' );
}

function http_auth_settings_page() {
   echo '<div class="wrap">';
   echo '<h2>HTTP Auth SETTINGS</h2>';
   echo '<form method="post" action="options.php">';
   settings_fields( 'http-auth-settings-group' );
   do_settings_sections( 'http-auth-settings-group' );
   $http_activated = esc_attr( get_option('http_auth_activate') );
   $http_activated_value = "on";
   $http_activated_checked = "";
   if($http_activated == "on"){
      $http_activated_checked = "checked";
   }
   $http_apply = esc_attr( get_option('http_auth_apply') );
   $http_apply_admin = "checked";
   $http_apply_site = "";
   if($http_apply == "site"){
      $http_apply_admin = "";
      $http_apply_site = "checked";
   }
   wp_enqueue_style( 'style', plugins_url('/style.css', __FILE__) );
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

if( !is_admin() ){
   $http_activated = esc_attr( get_option('http_auth_activate') );
   if($http_activated != "on"){
      return;
   }
   $http_applicable = esc_attr( get_option('http_auth_apply') );
   if( $http_applicable == "admin" ){
      if( strpos($_SERVER['REQUEST_URI'], '/wp-admin') === false && strpos($_SERVER['REQUEST_URI'], '/wp-login') === false ){
         return;
      }
   }
   $realm = 'Restricted Site';
   $username = esc_attr( get_option('http_auth_username') );
   $password = esc_attr( get_option('http_auth_password') );

   $user = $_SERVER['PHP_AUTH_USER'];
   $pass = $_SERVER['PHP_AUTH_PW'];

   if ( !($user == $username && $pass == $password) ){
      $message = esc_attr( get_option('http_auth_message') );
      header('WWW-Authenticate: Basic realm="'.$realm.'"');
      header('HTTP/1.0 401 Unauthorized');
      if( empty($message)){
         $message = "This Site is Restricted. Please contact the administrator for access.";
      }
      die ( http_auth_cancel_page($message) );
   }
}

function http_auth_cancel_page($message = ''){
   $sitename = get_bloginfo ( 'name' );
   return '<html>
               <head>
                  <meta http-equiv="Content-Type" content="text/html; charset=utf-8">
                  <title>'.$sitename.' | Restricted Site</title>
                  <link rel="stylesheet" href="'.plugins_url('/style.css', __FILE__).'" type="text/css">
               </head>
               <body class="http-restricted">
                  <p>'.$message.'</p>
               </body>
            </html>';
}

if(function_exists("add_action") && function_exists("add_filter")) { 
   $plugin = plugin_basename(__FILE__); 
   add_filter("plugin_action_links_$plugin", 'http_auth_settings_link' );
   
   add_action( 'admin_menu', 'http_auth_menu' );
}
