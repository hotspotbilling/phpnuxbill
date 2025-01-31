<?php
/**
 *  PHP Mikrotik Billing (https://github.com/hotspotbilling/phpnuxbill/)
 *  by https://t.me/ibnux
 **/

if(Admin::getID()){
    r2(getUrl('dashboard'));
}if(User::getID()){
    r2(getUrl('home'));
}else{
    r2(getUrl('login'));
}
