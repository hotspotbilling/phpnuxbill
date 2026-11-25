<?php

/**
 *  PHP Mikrotik Billing (https://github.com/hotspotbilling/phpnuxbill/)
 *  by https://t.me/ibnux
 **/


class Lang
{
    public static function T($key)
    {
        global $_L, $lan_file, $config;

        $key = preg_replace('/\s+/', ' ', $key);
        // no language file loaded (a light RADIUS request): never translate or write the empty list over the file
        if (empty($lan_file)) {
            return $key;
        }
        if (!empty($_L[$key])) {
            return $_L[$key];
        }
        $val = $key;
        $key = Lang::sanitize($key);
        if (isset($_L[$key])) {
            return $_L[$key];
        } else if (isset($_L[$key])) {
            return $_L[$key];
        } else {
            $iso = Lang::getIsoLang()[$config['language']] ?? '';
            if (empty($iso)) {
                return $val;
            }
            if ($iso != 'en' && !empty($val)) {
                $temp = Lang::translate($val, $iso);
                if ($temp === null) {
                    // not translated now (no answer, or the service refused): shown in English and not saved, so it is
                    // translated on a later visit instead of staying English for good
                    return $val;
                }
                $val = $temp;
            }
            $_L[$key] = $val;
            // readable text (not ا-style codes), so a change to a tracked language file shows as one line in git
            file_put_contents($lan_file, json_encode($_L, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES));
            return $val;
        }
    }

    /**
     * Whether a language chosen by a visitor (the language menu, its cookie, the session, the customer's saved choice) can be
     * used: a plain name with a file in system/lan. The name becomes a file path that is read and written, so nothing else is.
     */
    public static function valid($name)
    {
        global $root_path;
        return self::validName($name) && $name !== 'country'
            && is_file($root_path . File::pathFixer('system/lan/' . $name . '.json'));
    }

    /** The languages that have a file in system/lan, sorted (for a language switcher) */
    public static function available()
    {
        global $root_path;
        $out = [];
        foreach (@scandir($root_path . File::pathFixer('system/lan/')) ?: [] as $f) {
            if (substr($f, -5) === '.json' && self::valid(substr($f, 0, -5))) {
                $out[] = substr($f, 0, -5);
            }
        }
        sort($out);
        return $out;
    }

    /** The one rule for a language name that may become a file name: letters, digits, "_" and "-" (portuguese-brazil), no path characters */
    public static function validName($name)
    {
        return is_string($name) && preg_match('/^[a-z][a-z0-9_-]{0,31}$/D', $name) === 1;
    }

    public static function sanitize($str)
    {
        return preg_replace("/[^A-Za-z0-9]/", '_', $str);;
    }

    public static function getIsoLang()
    {
        global $isolang, $root_path;
        if (empty($isolang) || count($isolang) == 0) {
            // from the install's own folder, not the current one: cron starts in the home folder, system/api.php in system/
            $isolang = json_decode(file_get_contents($root_path . File::pathFixer("system/lan/country.json")), true);
        }
        return $isolang;
    }

    public static function htmlspecialchars($var)
    {
        return htmlspecialchars($var);
    }

    public static function moneyFormat($var)
    {
        global $config;
        return $config['currency_code'] . ' ' . number_format($var, 0, $config['dec_point'], $config['thousands_sep']);
    }

    public static function phoneFormat($phone)
    {
        global $config;
        if (Validator::UnsignedNumber($phone) && !empty($config['country_code_phone'])) {
            return preg_replace('/^0/',  $config['country_code_phone'], $phone);
        } else {
            return $phone;
        }
    }

    public static function dateFormat($date)
    {
        global $config;
        return date($config['date_format'], strtotime($date));
    }

    public static function dateTimeFormat($date)
    {
        global $config;
        if (strtotime($date) < strtotime("2000-01-01 00:00:00")) {
            return "";
        } else {
            return date($config['date_format'] . ' H:i', strtotime($date));
        }
    }

    public static function dateAndTimeFormat($date, $time)
    {
        global $config;
        return date($config['date_format'] . ' H:i', strtotime("$date $time"));
    }

    public static function timeElapsed($datetime, $full = false)
    {
        $now = new DateTime(date("Y-m-d H:i:s"));
        $ago = new DateTime($datetime);
        $diff = $now->diff($ago);
        $diff->w = floor($diff->d / 7);
        $diff->d -= $diff->w * 7;

        $string = array(
            'y' => Lang::T('year'),
            'm' => Lang::T('month'),
            'w' => Lang::T('week'),
            'd' => Lang::T('day'),
            'h' => Lang::T('hour'),
            'i' => Lang::T('minute'),
            's' => Lang::T('second'),
        );
        foreach ($string as $k => &$v) {
            if ($diff->$k) {
                $v = $diff->$k . ' ' . $v . ($diff->$k > 1 ? '' : '');
            } else {
                unset($string[$k]);
            }
        }
        $when = "";
        if (time() > strtotime($datetime)) {
            $when = Lang::T('ago');
        } else {
            $when = '';
        }
        if (!$full)
            $string = array_slice($string, 0, 1);
        if ($string) {
            if (empty($when)) {
                return '<b>' . implode(', ', $string) . '</b>';
            } else {
                return implode(', ', $string) . ' ' . $when;
            }
        } else {
            return Lang::T('just now');
        }
    }

    public static function nl2br($text)
    {
        return nl2br($text);
    }

    public static function arrayCount($arr)
    {
        if (is_array($arr)) {
            return count($arr);
        } else if (is_object($arr)) {
            return count($arr);
        } else {
            return 0;
        }
    }

    public static function getNotifText($key)
    {
        global $_notifmsg, $_notifmsg_default;
        if (isset($_notifmsg[$key])) {
            return $_notifmsg[$key];
        } else {
            return $_notifmsg_default[$key];
        }
    }

    public static function ucWords($text)
    {
        return ucwords(str_replace('_', ' ', $text));
    }

    public static function randomUpLowCase($text)
    {
        $jml = strlen($text);
        $result = '';
        for ($i = 0; $i < $jml; $i++) {
            if (rand(0, 99) % 2) {
                $result .= strtolower(substr($text, $i, 1));
            } else {
                $result .= substr($text, $i, 1);
            }
        }
        return $result;
    }

    /**
     * $pad_type
     * 0 Left
     * 1 right
     * 2 center
     * */
    public static function pad($text, $pad_string = ' ', $pad_type = 0)
    {
        global $config;
        $cols = 37;
        if ($config['printer_cols']) {
            $cols = $config['printer_cols'];
        }
        $text = trim($text);
        $texts = explode("\n", $text);
        if (count($texts) > 1) {
            $text = '';
            foreach ($texts as $t) {
                $text .= self::pad(trim($t), $pad_string, $pad_type) . "\n";
            }
            return $text;
        } else {
            return str_pad(trim($text), $cols, $pad_string, $pad_type);
        }
    }

    public static function pads($textLeft, $textRight, $pad_string = ' ')
    {
        global $config;
        $cols = 37;
        if ($config['printer_cols']) {
            $cols = $config['printer_cols'];
        }
        return $textLeft . str_pad($textRight, $cols - strlen($textLeft), $pad_string, 0);
    }

    /**
     * The text translated online, or null when it could not be. After a failure (Google answers "too many requests" to a
     * server that asks a lot), nothing more is asked for 15 minutes, so pages are not slowed by one wait per missing text.
     */
    public static function translate($txt, $to = 'id')
    {
        global $CACHE_PATH;
        static $downUntil = 0; // when the pause after a failure ends (not for ever: a long-running worker asks again after it)
        static $emptyUntil = []; // per text and language: an answer with no translation only holds back that text
        $pause = ($CACHE_PATH ?: sys_get_temp_dir()) . DIRECTORY_SEPARATOR . 'lang_translate_pause';
        $emptyKey = $to . '|' . $txt;
        if (time() < $downUntil || (is_file($pause) && time() - filemtime($pause) < 900) || time() < ($emptyUntil[$emptyKey] ?? 0)) {
            return null;
        }
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, "https://translate.google.com/m?hl=en&sl=en&tl=" . urlencode($to) . "&ie=UTF-8&prev=_m&q=" . urlencode($txt));
        curl_setopt($ch, CURLOPT_FOLLOWLOCATION, 1);
        curl_setopt($ch, CURLOPT_USERAGENT, "Mozilla/5.0 (iPhone; CPU OS 13_5 like Mac OS X) AppleWebKit/605.1.15 (KHTML, like Gecko) FxiOS/28.1 Mobile/15E148 Safari/605.1.15");
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 2);
        curl_setopt($ch, CURLOPT_TIMEOUT, 5);
        curl_setopt($ch, CURLOPT_HEADER, 0);
        $hasil = curl_exec($ch);
        $code = (int) curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);
        if (is_string($hasil) && $code === 200 && ($p = strpos($hasil, '<div class="result-container">')) !== false) {
            $rest = substr($hasil, $p + strlen('<div class="result-container">'));
            // real markup is stripped first, while entity-encoded text such as the placeholder "&lt;Class&gt;" is still text;
            // decoded after that, and when the English text had no "<" the result gets none either (never markup from a service)
            $out = trim(html_entity_decode(strip_tags(substr($rest, 0, (int) strpos($rest, '</div'))), ENT_QUOTES | ENT_HTML5, 'UTF-8'));
            if (strpos($txt, '<') === false) {
                $out = str_replace(['<', '>'], ['&lt;', '&gt;'], $out);
            }
            if ($out !== '') {
                return $out;
            }
            // the service answered but had nothing for this text: only this text and language wait, not every other
            $emptyUntil[$emptyKey] = time() + 900;
            return null;
        }
        $downUntil = time() + 900;
        @touch($pause);
        return null;
    }

    public static function maskText($text)
    {
        $len = strlen($text);
        if ($len < 3) {
            return "***";
        } else if ($len < 5) {
            return substr($text, 0, 1) . "***" . substr($text, -1, 1);
        } else if ($len < 8) {
            return substr($text, 0, 2) . "***" . substr($text, -2, 2);
        } else {
            return substr($text, 0, 4) . "******" . substr($text, -3, 3);
        }
    }

}
