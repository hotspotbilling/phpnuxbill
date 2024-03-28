<?php
/**
 *  PHP Mikrotik Billing (https://github.com/hotspotbilling/phpnuxbill/)
 *  by https://t.me/ibnux
 **/

if(Admin::getID()){
    r2(U.'dashboard');
}if(User::getID()){
    r2(U.'home');
}else{
    r2(U.'login');
}
