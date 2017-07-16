<?php 

/**
 * @package http_auth
 */

class Http_Auth {
  
  /**
   * Pop up the http auth to the non-administrators if it is activated
   */
   public static function init() {		
    $http_activated = esc_attr( get_option('http_auth_activate') );

    if($http_activated == "on") {
      $http_applicable = esc_attr( get_option('http_auth_apply') );
      if( $http_applicable == "admin" ) {
        if( strpos($_SERVER['REQUEST_URI'], '/wp-admin') === false && strpos($_SERVER['REQUEST_URI'], '/wp-login') === false ) {
           return;
        }
      }
      $realm = 'Restricted Site';
      $username = esc_attr( get_option('http_auth_username') );
      $password = esc_attr( get_option('http_auth_password') );

      $user = $_SERVER['PHP_AUTH_USER'];
      $pass = $_SERVER['PHP_AUTH_PW'];

      if ( !($user == $username && $pass == $password) ) {
        $message = esc_attr( get_option('http_auth_message') );
        header('WWW-Authenticate: Basic realm="'.$realm.'"');
        header('HTTP/1.0 401 Unauthorized');
        if( empty($message)) {
           $message = "This Site is Restricted. Please contact the administrator for access.";
        }
        die (self::http_auth_cancel_page($message) );
      }
    }
  }

  /**
   * Returns the Cancel Message 
   */
  public static function http_auth_cancel_page($message = '') {
    $sitename = get_bloginfo ( 'name' );
    return '<html>
              <head>
                <meta http-equiv="Content-Type" content="text/html; charset=utf-8">
                <title>'.$sitename.' | Restricted Site</title>
                <link rel="stylesheet" href="'.plugins_url('/css/style.min.css', __FILE__).'" type="text/css">
              </head>
              <body class="http-restricted">
                <p>'.$message.'</p>
              </body>
           </html>';
  }
}