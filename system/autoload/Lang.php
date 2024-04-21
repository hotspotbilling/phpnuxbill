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
        if(is_array($_SESSION['Lang'])){
            $_L = array_merge($_L, $_SESSION['Lang']);
        }
        $key = preg_replace('/\s+/', ' ', $key);
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
            $iso = Lang::getIsoLang()[$config['language']];
            if (empty($iso)) {
                return $val;
            }
            if (!empty($iso) && !empty($val)) {
                $temp = Lang::translate($val, $iso);
                if (!empty($temp)) {
                    $val = $temp;
                }
            }
            $_L[$key] = $val;
            $_SESSION['Lang'][$key] = $val;
            file_put_contents($lan_file, json_encode($_SESSION['Lang'], JSON_PRETTY_PRINT));
            return $val;
        }
    }

    public static function sanitize($str)
    {
        return preg_replace("/[^A-Za-z0-9]/", '_', $str);;
    }

    public static function getIsoLang()
    {
        global $isolang;
        if (empty($isolang) || count($isolang) == 0) {
            $isolang = json_decode(file_get_contents(File::pathFixer("system/lan/country.json")), true);
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
        $now = new DateTime;
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
                $v = $diff->$k . ' ' . $v . ($diff->$k > 1 ? 's' : '');
            } else {
                unset($string[$k]);
            }
        }

        if (!$full)
            $string = array_slice($string, 0, 1);
        return $string ? implode(', ', $string) . ' ago' : 'just now';
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

    public static function translate($txt, $to = 'id')
    {
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, "https://translate.google.com/m?hl=en&sl=en&tl=$to&ie=UTF-8&prev=_m&q=" . urlencode($txt));
        curl_setopt($ch, CURLOPT_FOLLOWLOCATION, 1);
        curl_setopt($ch, CURLOPT_USERAGENT, "Mozilla/5.0 (iPhone; CPU OS 13_5 like Mac OS X) AppleWebKit/605.1.15 (KHTML, like Gecko) FxiOS/28.1 Mobile/15E148 Safari/605.1.15");
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 2);
        curl_setopt($ch, CURLOPT_TIMEOUT, 5);
        curl_setopt($ch, CURLOPT_HEADER, 0);
        $hasil = curl_exec($ch);
        curl_close($ch);
        $temp = explode('<div class="result-container">', $hasil);
        if (count($temp) > 0) {
            $temp =  explode("</div", $temp[1]);
            if (!empty($temp[0])) {
                return $temp[0];
            }
        }
        return $txt;
    }

    public static function maskText($text){
        $len = strlen($text);
        if($len < 3){
            return "***";
        }else if($len<5){
            return substr($text,0,1)."***".substr($text,-1,1);
        }else if($len<8){
            return substr($text,0,2)."***".substr($text,-2,2);
        }else{
            return substr($text,0,4)."******".substr($text,-3,3);
        }
    }
}
