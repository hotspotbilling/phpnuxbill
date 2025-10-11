<?php
/**
 *  PHP Mikrotik Billing (https://github.com/hotspotbilling/phpnuxbill/)
 *  by https://t.me/ibnux
 **/

session_start();

/**
 * Validate and sanitize input
 * 
 * @param string $input The input to validate and sanitize
 * @param string $type The type of input (mac, ip, router, key, hostname, referral)
 * @return string|false Sanitized input or false if invalid
 */
function validateInput($input, $type) {
    if (empty($input)) {
        return false;
    }
    
    $input = trim($input);
    
    switch ($type) {
        case 'mac':
            // MAC address format: 00:11:22:33:44:55 or 001122334455 or 00-11-22-33-44-55
            $input = preg_replace('/[^a-fA-F0-9]/', '', $input);
            return (strlen($input) == 12 && ctype_xdigit($input)) ? $input : false;
            
        case 'ip':
            // IPv4 validation
            return filter_var($input, FILTER_VALIDATE_IP) ? $input : false;
            
        case 'router':
            // Allow alphanumeric, dash, underscore, dot
            if (preg_match('/^[a-zA-Z0-9\-_\.]{1,64}$/', $input)) {
                return $input;
            }
            return false;
            
        case 'key':
            // Hexadecimal key validation
            if (preg_match('/^[a-fA-F0-9]{1,128}$/', $input)) {
                return $input;
            }
            return false;
            
        case 'hostname':
            // Hostname validation
            if (preg_match('/^[a-zA-Z0-9\-\.]{1,64}$/', $input)) {
                return $input;
            }
            return false;
            
        case 'referral':
            // Based on the generateReferralCode method, referral codes are alphanumeric
            // between 4-20 characters (default 8)
            if (preg_match('/^[a-zA-Z0-9]{4,20}$/', $input)) {
                return $input;
            }
            return false;
            
        default:
            return false;
    }
}

// Validate and sanitize MAC address
if(isset($_GET['nux-mac'])) {
    $sanitized = validateInput($_GET['nux-mac'], 'mac');
    if ($sanitized !== false) {
        $_SESSION['nux-mac'] = $sanitized;
    }
}

// Validate and sanitize IP address
if(isset($_GET['nux-ip'])) {
    $sanitized = validateInput($_GET['nux-ip'], 'ip');
    if ($sanitized !== false) {
        $_SESSION['nux-ip'] = $sanitized;
    }
}

// Validate and sanitize router
if(isset($_GET['nux-router'])) {
    $sanitized = validateInput($_GET['nux-router'], 'router');
    if ($sanitized !== false) {
        $_SESSION['nux-router'] = $sanitized;
    }
}

// Validate and sanitize key (chap id and chap challenge)
if(isset($_GET['nux-key'])) {
    $sanitized = validateInput($_GET['nux-key'], 'key');
    if ($sanitized !== false) {
        $_SESSION['nux-key'] = $sanitized;
    }
}

// Validate and sanitize hostname
if(isset($_GET['nux-hostname'])) {
    $sanitized = validateInput($_GET['nux-hostname'], 'hostname');
    if ($sanitized !== false) {
        $_SESSION['nux-hostname'] = $sanitized;
    }
}

// Validate and sanitize referral code
if(isset($_GET['ref'])) {
    $sanitized = validateInput($_GET['ref'], 'referral');
    if ($sanitized !== false) {
        $_SESSION['nux-ref'] = $sanitized;
    }
}


require_once 'system/vendor/autoload.php';
require_once 'system/boot.php';
App::_run();
