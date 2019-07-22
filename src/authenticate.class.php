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

		global $pdo;

		$sql = "select user_id as id, user_password as password from elo_user where user_email = :email limit 1";
		$statement = $pdo->prepare($sql);
		$statement->bindValue(':email',$email);
		$statement->execute();
		
		$result = $statement->fetch(PDO::FETCH_ASSOC);

        if ( $result ) {

            $user = $result['id'];

        } else {
            
            throw new AuthException( _("This e-mail address was not found in the database.") );

        }

        require_once( "PasswordHash.php" );

        $hasher = new PasswordHash( 8, TRUE );

        if ( !$hasher->CheckPassword( $password, $result["password"] ) ) {
            
            throw new AuthException( _("Invalid password.") );
            
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
        
            throw new AuthException( _("Could not set cookie.") );
        
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

		global $pdo;
		
        list( $id, $expiration, $hmac ) = explode( '|', $_COOKIE[COOKIE_AUTH] );

		$sql = "insert into elo_user_login (user_id) values (:id)";
		$statement = $pdo->prepare($sql);
		$statement->bindValue(':id', (int)$id, PDO::PARAM_INT);
		$statement->execute();
		
        return $id;

    }

}

class AuthException extends Exception {}
