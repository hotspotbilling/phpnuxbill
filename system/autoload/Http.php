<?php

/**
 *  PHP Mikrotik Billing (https://github.com/hotspotbilling/phpnuxbill/)
 *  by https://t.me/ibnux
 **/

/**
 *  using proxy, add this variable in config.php
 *  $http_proxy  = '127.0.0.1:3128';
 *  if proxy using authentication, use this parameter
 *  $http_proxyauth = 'user:password';
 **/

class Http
{
    private static $notifying = false;

    /**
     * Tells the admins on Telegram that a request failed. The message is itself a request: one that fails while telling is not
     * told again (no endless loop when Telegram is the one failing), and it waits 15 seconds at most in total (connection and transfer).
     */
    private static function notify($label, $url, $error)
    {
        if (self::$notifying) {
            return;
        }
        self::$notifying = true;
        try {
            Message::sendTelegram($label . "\n" . _get('_route') . "\n\n" . $url . "\n" . $error, null, '', 15);
        } catch (Throwable $e) {
        } finally {
            self::$notifying = false;
        }
    }

    /**
     * A time limit in milliseconds. Callers pass either seconds (15) or milliseconds (3000, 5000: the defaults here); a value
     * of 100 or more is taken as milliseconds. 0 or less would mean "no limit" to cURL, so it becomes the 3 s default.
     * CURLOPT_TIMEOUT_MS limits the whole transfer, not only the connection.
     */
    public static function timeoutMs($value): int
    {
        // ['ms' => N]: milliseconds said outright, never read as seconds (a caller that already has milliseconds passes this)
        if (is_array($value)) {
            $ms = (float) ($value['ms'] ?? 0);
            return $ms > 0 ? max(1, (int) ceil($ms)) : 3000;
        }
        $v = (float) $value;
        if ($v <= 0) {
            return 3000;
        }
        // at least 1 ms: a positive limit that rounds down to 0 would mean "no limit" to cURL
        return max(1, (int) ceil($v >= 100 ? $v : $v * 1000));
    }

    /**
     * $error: set to cURL's error text when the request did not reach the server or timed out, '' otherwise.
     * $status: the HTTP status code of the answer (0 when there was none).
     */
    public static function getData($url, $headers = [], $connect_timeout = 3000, $wait_timeout = 15000, &$error = null, &$status = null)
    {
        global $http_proxy, $http_proxyauth, $admin;
        $error = '';
        $status = 0;
        $error_msg = '';
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_POST, 0);
        curl_setopt($ch, CURLOPT_CONNECTTIMEOUT_MS, self::timeoutMs($connect_timeout));
        curl_setopt($ch, CURLOPT_TIMEOUT_MS, self::timeoutMs($wait_timeout));
        if (is_array($headers) && count($headers) > 0) {
            curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
        }
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        if (!empty($http_proxy)) {
            curl_setopt($ch, CURLOPT_PROXY, $http_proxy);
            if (!empty($http_proxyauth)) {
                curl_setopt($ch, CURLOPT_PROXYUSERPWD, $http_proxyauth);
            }
        }
        $server_output = curl_exec($ch);
        $status = (int) curl_getinfo($ch, CURLINFO_RESPONSE_CODE);
        if (curl_errno($ch)) {
            $error_msg = curl_error($ch);
            $error = $error_msg !== '' ? $error_msg : 'cURL error ' . curl_errno($ch);
        }
        curl_close($ch);
        if ($admin && $error_msg) {
            self::notify("Http::getData Error:", $url, $error_msg);
            return $error_msg;
        }
        return (!empty($server_output)) ? $server_output : $error_msg;
    }

    public static function postJsonData($url, $array_post, $headers = [], $basic = null, $connect_timeout = 3000, $wait_timeout = 15000)
    {
        global $http_proxy, $http_proxyauth, $admin;
        $headers[] = 'Content-Type: application/json';
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_POST, 1);
        curl_setopt($ch, CURLOPT_CONNECTTIMEOUT_MS, self::timeoutMs($connect_timeout));
        curl_setopt($ch, CURLOPT_TIMEOUT_MS, self::timeoutMs($wait_timeout));
        curl_setopt($ch, CURLOPT_VERBOSE, false);
        curl_setopt($ch, CURLINFO_HEADER_OUT, false);
        if (!empty($http_proxy)) {
            curl_setopt($ch, CURLOPT_PROXY, $http_proxy);
            if (!empty($http_proxyauth)) {
                curl_setopt($ch, CURLOPT_PROXYUSERPWD, $http_proxyauth);
            }
        }
        curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($array_post));
        if (is_array($headers) && count($headers) > 0) {
            curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
        }
        if (!empty($basic)) {
            curl_setopt($ch, CURLOPT_USERPWD, $basic);
        }
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        $server_output = curl_exec($ch);
        if (curl_errno($ch)) {
            $error_msg = curl_error($ch);
        }
        curl_close($ch);
        if ($admin && $error_msg) {
            self::notify("Http::postJsonData:", $url, $error_msg);
            return $error_msg;
        }
        return (!empty($server_output)) ? $server_output : $error_msg;
    }


    public static function postData($url, $array_post, $headers = [], $basic = null, $connect_timeout = 3000, $wait_timeout = 15000)
    {
        global $http_proxy, $http_proxyauth, $admin;
        $headers[] = 'Content-Type: application/x-www-form-urlencoded';
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_POST, 1);
        curl_setopt($ch, CURLOPT_CONNECTTIMEOUT_MS, self::timeoutMs($connect_timeout));
        curl_setopt($ch, CURLOPT_TIMEOUT_MS, self::timeoutMs($wait_timeout));
        curl_setopt($ch, CURLOPT_VERBOSE, false);
        curl_setopt($ch, CURLINFO_HEADER_OUT, false);
        if (!empty($http_proxy)) {
            curl_setopt($ch, CURLOPT_PROXY, $http_proxy);
            if (!empty($http_proxyauth)) {
                curl_setopt($ch, CURLOPT_PROXYUSERPWD, $http_proxyauth);
            }
        }
        curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($array_post));
        if (is_array($headers) && count($headers) > 0) {
            curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
        }
        if (!empty($basic)) {
            curl_setopt($ch, CURLOPT_USERPWD, $basic);
        }
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        $server_output = curl_exec($ch);
        if (curl_errno($ch)) {
            $error_msg = curl_error($ch);
        }
        curl_close($ch);
        if ($admin && $error_msg) {
            self::notify("Http::postData Error:", $url, $error_msg);
            return $error_msg;
        }
        return (!empty($server_output)) ? $server_output : $error_msg;
    }
}
