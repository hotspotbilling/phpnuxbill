<?php
/**
 *  PHP Mikrotik Billing (https://github.com/hotspotbilling/phpnuxbill/)
 *  by https://t.me/ibnux
 **/

_admin();
$ui->assign('_title', 'Community');
$ui->assign('_system_menu', 'community');

$action = $routes['1'];
$ui->assign('_admin', $admin);

switch ($action) {
    case 'rollback':
        $ui->assign('_title', 'Rollback Update');
        $masters = json_decode(Http::getData("https://api.github.com/repos/hotspotbilling/phpnuxbill/commits?per_page=100",['User-Agent: Mozilla/5.0 (Macintosh; Intel Mac OS X 10.15; rv:125.0) Gecko/20100101 Firefox/125.0']), true);
        $devs = json_decode(Http::getData("https://api.github.com/repos/hotspotbilling/phpnuxbill/commits?sha=Development&per_page=100",['User-Agent: Mozilla/5.0 (Macintosh; Intel Mac OS X 10.15; rv:125.0) Gecko/20100101 Firefox/125.0']), true);

        $ui->assign('masters', $masters);
        $ui->assign('devs', $devs);
        $ui->display('community-rollback.tpl');
        break;
    default:
        $ui->display('community.tpl');
}