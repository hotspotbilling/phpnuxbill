<?php

/**
 *  PHP Mikrotik Billing (https://github.com/hotspotbilling/phpnuxbill/)
 *  by https://t.me/ibnux
 **/

class Captcha
{
    /**
     * Get active captcha provider: 'none', 'recaptcha', 'turnstile'
     */
    public static function getProvider()
    {
        global $config;
        $provider = $config['captcha_provider'] ?? 'none';
        return in_array($provider, ['recaptcha', 'turnstile']) ? $provider : 'none';
    }

    /**
     * The older single setting 'captcha_target': 'all', 'admin', 'customer' or 'plugin' ('both' meant 'all').
     * Only read until Settings > App has been saved with the check boxes (captcha_on_admin ...).
     */
    public static function getTarget()
    {
        global $config;
        $target = $config['captcha_target'] ?? 'all';
        if ($target === 'both') {
            return 'all';
        }
        if ($target === 'merchant') {
            return 'plugin'; // saved for a short while before the plugin target was named
        }
        return in_array($target, ['admin', 'customer', 'plugin', 'all']) ? $target : 'all';
    }

    /**
     * Is the captcha switched on for this kind of login? Contexts: 'admin', 'customer', and 'plugin' (the logins
     * plugins add, such as the Hotspot merchant portal; a plugin asks with Captcha::renderWidget('plugin') and
     * Captcha::verify('plugin')). Each has its own check box in Settings > App (captcha_on_<context> = yes / no).
     */
    public static function isContextOn($context)
    {
        global $config;
        $saved = $config['captcha_on_' . $context] ?? null;
        if ($saved !== null) {
            return $saved === 'yes';
        }
        $target = self::getTarget();
        return $target === 'all' || $target === $context;
    }

    /**
     * Check if captcha is enabled for a given context ('admin', 'customer' or 'plugin')
     */
    public static function isEnabled($context = 'admin')
    {
        $provider = self::getProvider();
        if ($provider === 'none') {
            return false;
        }

        if (!self::isContextOn($context)) {
            return false;
        }

        $siteKey = self::getSiteKey();
        $secretKey = self::getSecretKey();

        return !empty($siteKey) && !empty($secretKey);
    }

    /**
     * Get Site Key for active provider
     */
    public static function getSiteKey()
    {
        global $config;
        $provider = self::getProvider();
        if ($provider === 'recaptcha') {
            return trim($config['recaptcha_site_key'] ?? '');
        } elseif ($provider === 'turnstile') {
            return trim($config['turnstile_site_key'] ?? '');
        }
        return '';
    }

    /**
     * Get Secret Key for active provider
     */
    public static function getSecretKey()
    {
        global $config;
        $provider = self::getProvider();
        if ($provider === 'recaptcha') {
            return trim($config['recaptcha_secret_key'] ?? '');
        } elseif ($provider === 'turnstile') {
            return trim($config['turnstile_secret_key'] ?? '');
        }
        return '';
    }

    /**
     * Render the Captcha widget HTML + JS script
     */
    public static function renderWidget($context = 'admin')
    {
        if (!self::isEnabled($context)) {
            return '';
        }

        $provider = self::getProvider();
        $siteKey = htmlspecialchars(self::getSiteKey(), ENT_QUOTES, 'UTF-8');

        if ($provider === 'turnstile') {
            return '
            <div class="form-group captcha-container" style="display: flex; justify-content: center; margin: 15px 0;">
                <script src="https://challenges.cloudflare.com/turnstile/v0/api.js" async defer></script>
                <div class="cf-turnstile" data-sitekey="' . $siteKey . '" data-theme="auto"></div>
            </div>';
        } elseif ($provider === 'recaptcha') {
            return '
            <div class="form-group captcha-container" style="display: flex; justify-content: center; margin: 15px 0;">
                <script src="https://www.google.com/recaptcha/api.js" async defer></script>
                <div class="g-recaptcha" data-sitekey="' . $siteKey . '"></div>
            </div>';
        }

        return '';
    }

    /**
     * Verify captcha response from POST submission
     */
    public static function verify($context = 'admin')
    {
        if (!self::isEnabled($context)) {
            return ['success' => true];
        }

        $provider = self::getProvider();
        $secretKey = self::getSecretKey();

        // Obtain token from POST request
        $token = '';
        if ($provider === 'turnstile') {
            $token = _post('cf-turnstile-response');
        } elseif ($provider === 'recaptcha') {
            $token = _post('g-recaptcha-response');
        }

        // Fallback check if field was named interchangeably
        if (empty($token)) {
            $token = _post('cf-turnstile-response') ?: _post('g-recaptcha-response');
        }

        if (empty($token)) {
            return [
                'success' => false,
                'message' => Lang::T('Please complete the Captcha verification')
            ];
        }

        $remoteIp = $_SERVER['REMOTE_ADDR'] ?? '';

        if ($provider === 'turnstile') {
            $url = 'https://challenges.cloudflare.com/turnstile/v0/siteverify';
            $postData = [
                'secret' => $secretKey,
                'response' => $token,
                'remoteip' => $remoteIp
            ];
            $result = self::sendPostRequest($url, $postData);
        } elseif ($provider === 'recaptcha') {
            $url = 'https://www.google.com/recaptcha/api/siteverify';
            $postData = [
                'secret' => $secretKey,
                'response' => $token,
                'remoteip' => $remoteIp
            ];
            $result = self::sendPostRequest($url, $postData);
        } else {
            return ['success' => true];
        }

        if (!$result['success']) {
            return [
                'success' => false,
                'message' => $result['message'] ?? Lang::T('Captcha verification failed. Please try again.')
            ];
        }

        $responseJson = json_decode($result['body'], true);
        if (isset($responseJson['success']) && $responseJson['success'] === true) {
            return ['success' => true];
        }

        // Extract error codes if present for logging
        $errorCodes = isset($responseJson['error-codes']) ? implode(', ', (array)$responseJson['error-codes']) : 'invalid-captcha';
        _log("Captcha validation failed for $context ($provider): $errorCodes", 'Security');

        return [
            'success' => false,
            'message' => Lang::T('Captcha verification failed. Please try again.')
        ];
    }

    /**
     * Send HTTP POST request with 5s timeout using cURL or stream context
     */
    private static function sendPostRequest($url, $data)
    {
        if (function_exists('curl_init')) {
            $ch = curl_init();
            curl_setopt($ch, CURLOPT_URL, $url);
            curl_setopt($ch, CURLOPT_POST, true);
            curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($data));
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($ch, CURLOPT_TIMEOUT, 6);
            curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 4);
            curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, true);

            $body = curl_exec($ch);
            $curlError = curl_error($ch);
            $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
            curl_close($ch);

            if ($body === false || !empty($curlError)) {
                _log("Captcha cURL error ($url): $curlError", 'Error');
                return [
                    'success' => false,
                    'message' => Lang::T('Unable to verify captcha. Network timeout or connection error.')
                ];
            }

            return ['success' => true, 'body' => $body, 'status' => $httpCode];
        }

        // Fallback to file_get_contents with stream context
        $options = [
            'http' => [
                'header' => "Content-type: application/x-www-form-urlencoded\r\n",
                'method' => 'POST',
                'content' => http_build_query($data),
                'timeout' => 6
            ]
        ];
        $context = stream_context_create($options);
        $body = @file_get_contents($url, false, $context);

        if ($body === false) {
            return [
                'success' => false,
                'message' => Lang::T('Unable to verify captcha. Network timeout or connection error.')
            ];
        }

        return ['success' => true, 'body' => $body, 'status' => 200];
    }
}
