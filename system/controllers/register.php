<?php

/**
 * PHP Mikrotik Billing (https://github.com/hotspotbilling/phpnuxbill/)
 * @copyright	Copyright (C) 2014-2015 PHP Mikrotik Billing
 * @license		GNU General Public License version 2 or later; see LICENSE.txt
 *
 * created by iBNuX
 **/

if (isset($routes['1'])) {
    $do = $routes['1'];
} else {
    $do = 'register-display';
}

$otpPath = 'system/cache/sms/';

switch ($do) {
    case 'post':
        $otp_code = _post('otp_code');
        $username = _post('username');
        $fullname = _post('fullname');
        $password = _post('password');
        $cpassword = _post('cpassword');
        $address = _post('address');
        $phonenumber = _post('username');
        $msg = '';
        if (Validator::Length($username, 35, 2) == false) {
            $msg .= 'Username should be between 3 to 55 characters' . '<br>';
        }
        if (Validator::Length($fullname, 36, 2) == false) {
            $msg .= 'Full Name should be between 3 to 25 characters' . '<br>';
        }
        if (!Validator::Length($password, 35, 2)) {
            $msg .= 'Password should be between 3 to 35 characters' . '<br>';
        }
        if ($password != $cpassword) {
            $msg .= $_L['PasswordsNotMatch'] . '<br>';
        }

        if(!empty($config['sms_url'])){
            $otpPath .= sha1($username.$db_password).".txt";
            run_hook('validate_otp'); #HOOK
            if(file_exists($otpPath) && time()-filemtime($otpPath)>300){
                unlink($otpPath);
                r2(U . 'register', 's', 'Verification code expired');
            }else if(file_exists($otpPath)){
                $code = file_get_contents($otpPath);
                if($code!=$otp_code){
                    $ui->assign('username', $username);
                    $ui->assign('fullname', $fullname);
                    $ui->assign('address', $address);
                    $ui->assign('phonenumber', $phonenumber);
                    $ui->assign('notify', '<div class="alert alert-success">
                    <button type="button" class="close" data-dismiss="alert">
                    <span aria-hidden="true">×</span>
                    </button>
                    <div>Verification code is Wrong</div></div>');
                    $ui->display('register-otp.tpl');
                    exit();
                }else{
                    unlink($otpPath);
                }
            }else{
                r2(U . 'register', 's', 'No Verification code');
            }
        }
        $d = ORM::for_table('tbl_customers')->where('username', $username)->find_one();
        if ($d) {
            $msg .= $_L['account_already_exist'] . '<br>';
        }
        if ($msg == '') {
            run_hook('register_user'); #HOOK
            $d = ORM::for_table('tbl_customers')->create();
            $d->username = $username;
            $d->password = $password;
            $d->fullname = $fullname;
            $d->address = $address;
            $d->phonenumber = $phonenumber;
            if ($d->save()) {
                $user = $d->id();
                r2(U . 'login', 's', $_L['Register_Success']);
            } else {
                $ui->assign('username', $username);
                $ui->assign('fullname', $fullname);
                $ui->assign('address', $address);
                $ui->assign('phonenumber', $phonenumber);
                $ui->assign('notify', '<div class="alert alert-danger">
                <button type="button" class="close" data-dismiss="alert">
                <span aria-hidden="true">×</span>
                </button>
                <div>Failed to register</div></div>');
                run_hook('view_otp_register'); #HOOK
                $ui->display('register-rotp.tpl');
            }
        } else {
            $ui->assign('username', $username);
            $ui->assign('fullname', $fullname);
            $ui->assign('address', $address);
            $ui->assign('phonenumber', $phonenumber);
            $ui->assign('notify', '<div class="alert alert-danger">
            <button type="button" class="close" data-dismiss="alert">
            <span aria-hidden="true">×</span>
            </button>
            <div>' . $msg . '</div></div>');
            $ui->display('register.tpl');
        }
        break;

    default:
        if(!empty($config['sms_url'])){
            $username = _post('username');
            if(!empty($username)){
                $d = ORM::for_table('tbl_customers')->where('username', $username)->find_one();
                if ($d) {
                    r2(U . 'register', 's', $_L['account_already_exist']);
                }
                if(!file_exists($otpPath)){
                    mkdir($otpPath);
                    touch($otpPath.'index.html');
                }
                $otpPath .= sha1($username.$db_password).".txt";
                if(file_exists($otpPath) && time()-filemtime($otpPath)<120){
                    $ui->assign('username', $username);
                    $ui->assign('notify', '<div class="alert alert-success">
                    <button type="button" class="close" data-dismiss="alert">
                    <span aria-hidden="true">×</span>
                    </button>
                    <div>Please wait '.(120-(time()-filemtime($otpPath))).' seconds before sending another SMS</div></div>');
                    $ui->display('register-otp.tpl');
                }else{
                    $otp = rand(100000,999999);
                    file_put_contents($otpPath, $otp);
                    sendSMS($username,$config['CompanyName']."\nYour Verification code are: $otp");
                    $ui->assign('username', $username);
                    $ui->assign('notify', '<div class="alert alert-success">
                    <button type="button" class="close" data-dismiss="alert">
                    <span aria-hidden="true">×</span>
                    </button>
                    <div>Verification code has been sent to your phone</div></div>');
                    $ui->display('register-otp.tpl');
                }
            }else{
                run_hook('view_otp_register'); #HOOK
                $ui->display('register-rotp.tpl');
            }
        }else{
            $ui->assign('username', "");
            $ui->assign('fullname', "");
            $ui->assign('address', "");
            $ui->assign('otp', false);
            run_hook('view_register'); #HOOK
            $ui->display('register.tpl');
        }
        break;
}
