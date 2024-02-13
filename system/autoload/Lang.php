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
        $L = $_SESSION['Lang'];
        if (!empty($_L[$key])) {
            return $_L[$key];
        }
        $val = $key;
        $md5 = md5($key);
        if (!empty($_L[$key])) {
            return $_L[$key];
        }else if (!empty($_L[$md5])) {
            return $_L[$md5];
        } else if (!empty($_L[str_replace(' ', '_', $key)])) {
            return $_L[str_replace(' ', '_', $key)];
        } else {
            $iso = Lang::getIsoLang()[$config['language']];
            if(!empty($iso) && !empty($val)){
                $temp = Lang::translate($val, $iso);
                if(!empty($temp)){
                    $val = $temp;
                }
            }
            $key = md5($key);
            $_L[$key] = $val;
            $_SESSION['Lang'][$key] = $val;
            file_put_contents(File::pathFixer('system/lan/' . $config['language'] . '/common.lan.json'), json_encode($_SESSION['Lang']));
            return $val;
        }
    }

    public static function getIsoLang(){
        global $isolang;
        if(empty($isolang) || count($isolang)==0){
            $isolang = json_decode(file_get_contents(File::pathFixer("system/lan/country.json")),true);
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

    public static function timeElapsed($time){
            $s = $time%60;
            $m = floor(($time%3600)/60);
            $h = floor(($time%86400)/3600);
            $d = floor(($time%2592000)/86400);
            $M = floor($time/2592000);
            $result = '';
            if($M>0){
                $result = $M.'m ';
            }
            if($d>0){
                $result .= $d.'d ';
            }else if($M>0){
                $result .= '0d ';
            }
            return "$result$h:$m:$s";
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

    public static function randomUpLowCase($text){
        $jml = strlen($text);
        $result = '';
        for($i = 0; $i < $jml;$i++){
            if(rand(0,99)%2){
                $result .= strtolower(substr($text,$i,1));
            }else{
                $result .= substr($text,$i,1);
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
    public static function pad($text, $pad_string = ' ', $pad_type = 0){
        global $config;
        $cols = 37;
        if($config['printer_cols']){
            $cols = $config['printer_cols'];
        }
        $text = trim($text);
        $texts = explode("\n", $text);
        if(count($texts)>1){
            $text = '';
            foreach($texts as $t){
                $text.= self::pad(trim($t), $pad_string, $pad_type)."\n";
            }
            return $text;
        }else{
            return str_pad(trim($text), $cols, $pad_string, $pad_type);
        }
    }

    public static function pads($textLeft, $textRight, $pad_string = ' '){
        global $config;
        $cols = 37;
        if($config['printer_cols']){
            $cols = $config['printer_cols'];
        }
        return $textLeft.str_pad($textRight, $cols-strlen($textLeft), $pad_string, 0);
    }

    public static function translate($txt, $to='id'){
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL,"https://translate.google.com/m?hl=en&sl=en&tl=$to&ie=UTF-8&prev=_m&q=".urlencode($txt));
        curl_setopt($ch, CURLOPT_FOLLOWLOCATION, 1);
        curl_setopt($ch, CURLOPT_USERAGENT, "Mozilla/5.0 (iPhone; CPU OS 13_5 like Mac OS X) AppleWebKit/605.1.15 (KHTML, like Gecko) FxiOS/28.1 Mobile/15E148 Safari/605.1.15");
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 2);
        curl_setopt($ch, CURLOPT_TIMEOUT, 60);
        curl_setopt ($ch, CURLOPT_HEADER, 0);
        $hasil = curl_exec ($ch);
        curl_close($ch);
        $temp = explode('<div class="result-container">', $hasil);
        if(count($temp)>0){
            $temp =  explode("</div", $temp[1]);
            if(!empty($temp[0])){
                return $temp[0];
            }
        }
        return $txt;
    }
}
