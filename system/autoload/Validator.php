<?php
/**
* PHP Mikrotik Billing (https://ibnux.github.io/phpmixbill/)


* @copyright	Copyright (C) 2014-2015 PHP Mikrotik Billing
* @license		GNU General Public License version 2 or later; see LICENSE.txt

**/

/**
 * Validator class
 */
class Validator{

    /**
     * String text finder
     *
     * @access private
     * @param string $string
     * @param array $hits
     * @return void
     */
    private static function textHit($string, $exclude=""){
        if(empty($exclude)) return false;
        if(is_array($exclude)){
            foreach($exclude as $text){
                if(strstr($string, $text)) return true;
            }
        }else{
            if(strstr($string, $exclude)) return true;
        }
        return false;
    }

    /**
     * Number compare
     *
     * @access private
     * @param int $integer
     * @param int $max
     * @param int $min
     * @return bool
     */
    private static function numberBetween($integer, $max=null, $min=0){
        if(is_numeric($min) && $integer <= $min) return false;
        if(is_numeric($max) && $integer >= $max) return false;
        return true;
    }

    /**
     * Email addres check
     *
     * @access public
     * @param string $string
     * @param array $exclude
     * @return bool
     */
    public static function Email($string, $exclude=""){
        if(self::textHit($string, $exclude)) return false;
        return (bool)preg_match("/^([a-z0-9])(([-a-z0-9._])*([a-z0-9]))*\@([a-z0-9])(([a-z0-9-])*([a-z0-9]))+(\.([a-z0-9])([-a-z0-9_-])?([a-z0-9])+)+$/i", $string);
    }

    /**
     * URL check
     *
     * @access public
     * @param strin $string
     * @return bool
     */
    public static function Url($string, $exclude=""){
        if(self::textHit($string, $exclude)) return false;
        return (bool)preg_match("/^(http|https|ftp):\/\/([A-Z0-9][A-Z0-9_-]*(?:\.[A-Z0-9][A-Z0-9_-]*)+):?(\d+)?\/?/i", $string);
    }

    /**
     * IP
     *
     * @access public
     * @param string $string
     * @return void
     */
    public static function Ip($string){
        return (bool)preg_match("/^(1?\d{1,2}|2([0-4]\d|5[0-5]))(\.(1?\d{1,2}|2([0-4]\d|5[0-5]))){3}$/", $string);
    }

    /**
     * Check if it is an number
     *
     * @access public
     * @param int $integer
     * @param int $max
     * @param int $min
     * @return bool
     */
    public static function Number($integer, $max=null, $min=0){
        if(preg_match("/^\-?\+?[0-9e1-9]+$/",$integer)){
            if(!self::numberBetween($integer, $max, $min)) return false;
            return true;
        }
        return false;
    }

    /**
     * Check if it is an unsigned number
     *
     * @access public
     * @param int $integer
     * @return bool
     */
    public static function UnsignedNumber($integer){
        return (bool)preg_match("/^\+?[0-9]+$/",$integer);
    }

    /**
     * Float
     *
     * @access public
     * @param string $string
     * @return bool
     */
    public static function Float($string){
        return (bool)($string==strval(floatval($string)))? true : false;
    }

    /**
     * Alpha check
     *
     * @access public
     * @param string $string
     * @return void
     */
    public static function Alpha($string){
        return (bool)preg_match("/^[a-zA-Z]+$/", $string);
    }

    /**
     * Alpha numeric check
     *
     * @access public
     * @param string $string
     * @return void
     */
    public static function AlphaNumeric($string){
        return (bool)preg_match("/^[0-9a-zA-Z]+$/", $string);
    }

    /**
     * Specific chars check
     *
     * @access public
     * @param string $string
     * @param array $allowed
     * @return void
     */
    public static function Chars($string, $allowed=array("a-z")){
        return (bool)preg_match("/^[" . implode("", $allowed) . "]+$/", $string);
    }

    /**
     * Check length of an string
     *
     * @access public
     * @param string $stirng
     * @param int $max
     * @param int $min
     * @return bool
     */
    public static function Length($string, $max=null, $min=0){
        $length = strlen($string);
        if(!self::numberBetween($length, $max, $min)) return false;
        return true;
    }

    /**
     * Hex color check
     *
     * @access public
     * @param string $string
     * @return void
     */
    public static function HexColor($string){
        return (bool)preg_match("/^(#)?([0-9a-f]{1,2}){3}$/i", $string);
    }

    /**
     * Data validation
     *
     * Does'nt matter how you provide the date
     * dd/mm/yyyy
     * dd-mm-yyyy
     * yyyy/mm/dd
     * yyyy-mm-dd
     *
     * @access public
     * @param string $string
     * @return bool
     */
    public static function Date($string){
        $date = date('Y', strtotime($string));
        return ($date == "1970" || $date == '') ? false : true;
    }

    /**
     * Older than check
     *
     * @access public
     * @param string $string
     * @param int $age
     * @return bool
     */
    public static function OlderThan($string, $age){
        $date = date('Y', strtotime($string));
        if($date == "1970" || $date == '') return false;
        return (date('Y') - $date) > $age ? true : false;
    }

    /**
     * XML valid
     *
     * @access public
     * @param string $string
     * @return bool
     */
    public static function Xml($string){
        $Xml = @simplexml_load_string($string);
        return ($Xml === false) ? false : true;
    }

    /**
     * Is filesize between
     *
     * @access public
     * @param string $file
     * @param int $max
     * @param int $min
     * @return bool
     */
    public static function FilesizeBetween($file, $max=null, $min=0){
        $filesize = filesize($file);
        return self::numberBetween($filesize, $max, $min);
    }

    /**
     * Is image width between
     *
     * @access public
     * @param string $image
     * @param int $max_width
     * @param int $min_width
     * @param int $max_height
     * @param int $min_height
     * @return void
     */
    public static function ImageSizeBetween($image, $max_width="", $min_width=0, $max_height="", $min_height=0){
        $size = getimagesize($image);
        if(!self::numberBetween($size[0], $max_width, $min_width)) return false;
        if(!self::numberBetween($size[1], $max_height, $min_height)) return false;
        return true;
    }

    /**
     * Phone numbers
     *
     * @access public
     * @param string $phone
     * @return bool
     */
    public static function Phone($phone){
        $formats = array(	'###-###-####',
            '####-###-###',
            '(###) ###-###',
            '####-####-####',
            '##-###-####-####',
            '####-####',
            '###-###-###',
            '#####-###-###',
            '##########',
            '####-##-##-##');
        $format = trim(preg_replace("/[0-9]/", "#", $phone));
        return (bool)in_array($format, $formats);
    }

}