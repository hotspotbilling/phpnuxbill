<?php

/**
 *  PHP Mikrotik Billing (https://github.com/hotspotbilling/phpnuxbill/)
 *  by https://t.me/ibnux
 **/


/**
 * Validator class
 */
class Widget
{

    public static function rows($rows, $result){
        $result .= '<div class="row">';
        foreach($rows as $row){

        }
        $result .= '</div>';
    }

    public static function columns($cols, $result){
        $c = count($cols);
        switch($c){
            case 1:
                $result .= '<div class="col-md-12">';
                break;
            case 2:
                $result .= '<div class="col-md-6">';
                break;
            case 3:
                $result .= '<div class="col-md-4">';
                break;
            case 4:
                $result .= '<div class="col-md-4">';
                break;
            case 5:
                $result .= '<div class="col-md-4">';
                break;
            default:
                $result .= '<div class="col-md-1">';
                break;
        }

        foreach($cols as $col){
        }
        $result .= '</div>';
    }
}