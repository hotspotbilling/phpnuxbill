<?php

/**
 *  PHP Mikrotik Billing (https://github.com/hotspotbilling/phpnuxbill/)
 *  by https://t.me/ibnux
 **/

_auth();
$action = $routes['1'];
$user = User::_info();
$ui->assign('_user', $user);

switch ($action) {
    case 'view':
        $mail = ORM::for_table('tbl_customers_inbox')->where('customer_id', $user['id'])->find_one($routes['2']);
        if(!$mail){
            r2(U. 'mail', 'e', Lang::T('Message Not Found'));
        }
        if($mail['date_read'] == null){
            $mail->date_read = date('Y-m-d H:i:s');
            $mail->save();
        }
        $ui->assign('mail', $mail);
        $ui->assign('tipe', 'view');
        $ui->assign('_system_menu', 'inbox');
        $ui->assign('_title', Lang::T('Inbox'));
        $ui->display('user-inbox.tpl');
    default:
        $q = _req('q');
        $limit = 40;
        $p = (int) _req('p', 0);
        $offset = $p * $limit;
        $query = ORM::for_table('tbl_customers_inbox')->where('customer_id', $user['id'])->order_by_desc('date_created');
        $query->limit($limit)->offset($offset);
        if(!empty($q)){
            $query->whereRaw("(subject like '%$q%' or body like '%$q%')");
        }
        $mails = $query->find_array();
        $ui->assign('tipe', '');
        $ui->assign('q', $q);
        $ui->assign('p', $p);
        $ui->assign('mails', $mails);
        $ui->assign('_system_menu', 'inbox');
        $ui->assign('_title', Lang::T('Inbox'));
        $ui->display('user-inbox.tpl');
}