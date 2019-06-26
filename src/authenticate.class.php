<?php

$cookiedomain = ($_SERVER['HTTP_HOST'] != 'localhost') ? $_SERVER['HTTP_HOST'] : false;

define( 'COOKIE_DOMAIN', $cookiedomain );
define( 'COOKIE_PATH', '/' );
define( 'COOKIE_AUTH', 'auth_elo' );

define( 'SECRET_KEY', 'dk;l1g34!851éds-fghjg4lui:è3afàzgq_f4fá.' );

class Authenticate {


    public function __construct(  ) {
    //$email, $password, $remember
        //$this->authenticate( $email, $password, $remember );
        
    }

    public function authenticate( $email, $password, $remember ) {

		global $db;

		$query = $db->query("select user_id as id, user_password as password from elo_user where user_email='".addslashes($email)."'");

        $result = $db->fetch_array($query);

        if ( $db->num_rows($query) == 1 ) {

            $user = $result['id'];

        } else {
            
            throw new AuthException( "This e-mail address was not found in the database." );

        }

        require_once( "PasswordHash.php" );

        $hasher = new PasswordHash( 8, TRUE );

        if ( !$hasher->CheckPassword( $password, $result["password"] ) ) {
            
            throw new AuthException( "Invalid password." );
            
        }
        
        $this->setCookie( $user, $remember );

    }
    
    private function setCookie( $id, $remember = false ) {

        if ( $remember ) {

            $expiration = time() + 1209600; // 14 days

        } else {

            $expiration = time() + 172800; // 48 hours

        }

        $cookie = $this->generateCookie( $id, $expiration );

        if ( !setcookie( COOKIE_AUTH, $cookie, $expiration, COOKIE_PATH, COOKIE_DOMAIN, false, true ) ) {
        
            throw new AuthException( "Could not set cookie." );
        
        }

    }
    
    private function generateCookie( $id, $expiration ) {

        $key = hash_hmac( 'md5', $id . $expiration, SECRET_KEY );
        $hash = hash_hmac( 'md5', $id . $expiration, $key );

        $cookie = $id . '|' . $expiration . '|' . $hash;

        return $cookie;

    }

    public static function logOut( ) {

        setcookie( COOKIE_AUTH, "", time() - 1209600, COOKIE_PATH, COOKIE_DOMAIN, false, true );

    }

    public static function validateAuthCookie() {

        if ( empty($_COOKIE[COOKIE_AUTH]) )
            return false;

        list( $id, $expiration, $hmac ) = explode( '|', $_COOKIE[COOKIE_AUTH] );

        if ( $expiration < time() )
            return false;

        $key = hash_hmac( 'md5', $id . $expiration, SECRET_KEY );
        $hash = hash_hmac( 'md5', $id . $expiration, $key );

        if ( $hmac != $hash )
            return false;

        return true;

    }

    public static function getUserId() {

		global $db;
		
        list( $id, $expiration, $hmac ) = explode( '|', $_COOKIE[COOKIE_AUTH] );

		//$db->query("update elo_user set user_lastvisit='".time()."' where user_id=".intval($id));
        $db->query("insert into elo_user_login (user_id) values ('".(int)$id."')");

        return $id;

    }

}

class AuthException extends Exception {}
