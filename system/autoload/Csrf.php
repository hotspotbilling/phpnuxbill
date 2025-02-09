<?php

/**
 *  PHP Mikrotik Billing (https://github.com/hotspotbilling/phpnuxbill/)
 *  by https://t.me/ibnux
 **/


 class Csrf
 {
     private const int TOKEN_LENGTH = 16;
     private const int TOKEN_EXPIRATION = 1800;
 
     /**
      * Generate a CSRF token.
      *
      * @param int $length
      * @return string
      */
     public static function generateToken(int $length = self::TOKEN_LENGTH): string
     {
         return bin2hex(random_bytes($length));
     }
 
     /**
      * Validate the provided CSRF token against the stored token.
      *
      * @param string $token
      * @param string $storedToken
      * @return bool
      */
     public static function validateToken(string $token, string $storedToken): bool
     {
         return hash_equals($token, $storedToken);
     }
 
     /**
      * Check if the CSRF token is valid.
      *
      * @param string|null $token
      * @return bool
      */
     public static function check(?string $token): bool
     {
         global $config;
 
         if ($config['csrf_enabled'] === 'yes') {
             if (isset($_SESSION['nux_csrf_token'], $_SESSION['nux_csrf_token_time'], $token)) {
                 $storedToken = $_SESSION['nux_csrf_token'];
                 $tokenTime = $_SESSION['nux_csrf_token_time'];
 
                 if (time() - $tokenTime > self::TOKEN_EXPIRATION) {
                     self::clearToken();
                     return false;
                 }
 
                 return self::validateToken($token, $storedToken);
             }
 
             return false;
         }
 
         return true; // CSRF is disabled
     }
 
     /**
      * Generate and store a new CSRF token in the session.
      *
      * @return string
      */
     public static function generateAndStoreToken(): string
     {
         $token = self::generateToken();
         $_SESSION['nux_csrf_token'] = $token;
         $_SESSION['nux_csrf_token_time'] = time();
         return $token;
     }
 
     /**
      * Clear the stored CSRF token from the session.
      *
      * @return void
      */
     public static function clearToken(): void
     {
         unset($_SESSION['nux_csrf_token'], $_SESSION['nux_csrf_token_time']);
     }
 }