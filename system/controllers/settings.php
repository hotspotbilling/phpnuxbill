<?php

/**
 *  PHP Mikrotik Billing (https://github.com/hotspotbilling/phpnuxbill/)
 *  by https://t.me/ibnux
 **/
_admin();
$ui->assign('_title', Lang::T('Settings'));
$ui->assign('_system_menu', 'settings');

$action = $routes['1'];
$ui->assign('_admin', $admin);

switch ($action) {
    case 'docs':
        $d = ORM::for_table('tbl_appconfig')->where('setting', 'docs_clicked')->find_one();
        if ($d) {
            $d->value = 'yes';
            $d->save();
        } else {
            $d = ORM::for_table('tbl_appconfig')->create();
            $d->setting = 'docs_clicked';
            $d->value = 'yes';
            $d->save();
        }
        r2('./docs');
        break;
    case 'devices':
        $files = scandir($DEVICE_PATH);
        $devices = [];
        foreach ($files as $file) {
            $ext = pathinfo($file, PATHINFO_EXTENSION);
            if ($ext == 'php') {
                $dev = pathinfo($file, PATHINFO_FILENAME);
                require_once $DEVICE_PATH . DIRECTORY_SEPARATOR . $file;
                $dvc = new $dev;
                if(method_exists($dvc, 'description')){
                    $arr = $dvc->description();
                    $arr['file'] = $dev;
                    $devices[] = $arr;
                }else{
                    $devices[] = [
                        'title' => $dev,
                        'description' => '',
                        'author' => 'unknown',
                        'url' => [],
                        'file' => $dev
                    ];
                }
            }
        }
        $ui->assign('devices', $devices);
        $ui->display('app-devices.tpl');
        break;
    case 'app':
        if (!in_array($admin['user_type'], ['SuperAdmin', 'Admin'])) {
            _alert(Lang::T('You do not have permission to access this page'), 'danger', "dashboard");
        }

        if (!empty(_get('testWa'))) {
            $result = Message::sendWhatsapp(_get('testWa'), 'PHPNuxBill Test Whatsapp');
            r2(U . "settings/app", 's', 'Test Whatsapp has been send<br>Result: ' . $result);
        }
        if (!empty(_get('testSms'))) {
            $result = Message::sendSMS(_get('testSms'), 'PHPNuxBill Test SMS');
            r2(U . "settings/app", 's', 'Test SMS has been send<br>Result: ' . $result);
        }
        if (!empty(_get('testEmail'))) {
            Message::sendEmail(_get('testEmail'), 'PHPNuxBill Test Email', 'PHPNuxBill Test Email Body');
            r2(U . "settings/app", 's', 'Test Email has been send');
        }
        if (!empty(_get('testTg'))) {
            $result = Message::sendTelegram('PHPNuxBill Test Telegram');
            r2(U . "settings/app", 's', 'Test Telegram has been send<br>Result: ' . $result);
        }

        $UPLOAD_URL_PATH = str_replace($root_path, '',  $UPLOAD_PATH);
        if (file_exists($UPLOAD_PATH . DIRECTORY_SEPARATOR . 'logo.png')) {
            $logo = $UPLOAD_URL_PATH . DIRECTORY_SEPARATOR . 'logo.png?' . time();
        } else {
            $logo = $UPLOAD_URL_PATH . DIRECTORY_SEPARATOR . 'logo.default.png';
        }
        $ui->assign('logo', $logo);
        $themes = [];
        $files = scandir('ui/themes/');
        foreach ($files as $file) {
            if (is_dir('ui/themes/' . $file) && !in_array($file, ['.', '..'])) {
                $themes[] = $file;
            }
        }
        $r = ORM::for_table('tbl_routers')->find_many();
        $ui->assign('r', $r);
        if (function_exists("shell_exec")) {
            $php = trim(shell_exec('which php'));
            if (empty($php)) {
                $php = 'php';
            }
        } else {
            $php = 'php';
        }
        if (empty($config['api_key'])) {
            $config['api_key'] = sha1(uniqid(rand(), true));
            $d = ORM::for_table('tbl_appconfig')->where('setting', 'api_key')->find_one();
            if ($d) {
                $d->value = $config['api_key'];
                $d->save();
            } else {
                $d = ORM::for_table('tbl_appconfig')->create();
                $d->setting = 'api_key';
                $d->value = $config['api_key'];
                $d->save();
            }
        }
        $ui->assign('_c', $config);
        $ui->assign('php', $php);
        $ui->assign('dir', str_replace('controllers', '', __DIR__));
        $ui->assign('themes', $themes);
        run_hook('view_app_settings'); #HOOK
        $ui->display('app-settings.tpl');
        break;

    case 'app-post':
        if (!in_array($admin['user_type'], ['SuperAdmin', 'Admin'])) {
            _alert(Lang::T('You do not have permission to access this page'), 'danger', "dashboard");
        }
        $company = _post('CompanyName');
        $custom_tax_rate = filter_var(_post('custom_tax_rate'), FILTER_SANITIZE_SPECIAL_CHARS);
        if (preg_match('/[^0-9.]/', $custom_tax_rate)) {
            r2(U . 'settings/app', 'e', 'Special characters are not allowed in tax rate');
            die();
        }
        run_hook('save_settings'); #HOOK
        if (!empty($_FILES['logo']['name'])) {
            if (function_exists('imagecreatetruecolor')) {
                if (file_exists($UPLOAD_PATH . DIRECTORY_SEPARATOR . 'logo.png')) unlink($UPLOAD_PATH . DIRECTORY_SEPARATOR . 'logo.png');
                File::resizeCropImage($_FILES['logo']['tmp_name'], $UPLOAD_PATH . DIRECTORY_SEPARATOR . 'logo.png', 1078, 200, 100);
                if (file_exists($_FILES['logo']['tmp_name'])) unlink($_FILES['logo']['tmp_name']);
            } else {
                r2(U . 'settings/app', 'e', 'PHP GD is not installed');
            }
        }
        if ($company == '') {
            r2(U . 'settings/app', 'e', Lang::T('All field is required'));
        } else {
            if ($radius_enable) {
                try {
                    require_once $DEVICE_PATH . DIRECTORY_SEPARATOR . "Radius.php";
                    (new Radius())->getTableNas()->find_many();
                } catch (Exception $e) {
                    $ui->assign("error_title", "RADIUS Error");
                    $ui->assign("error_message", "Radius table not found.<br><br>" .
                        $e->getMessage() .
                        "<br><br>Download <a href=\"https://raw.githubusercontent.com/hotspotbilling/phpnuxbill/Development/install/radius.sql\">here</a> or <a href=\"https://raw.githubusercontent.com/hotspotbilling/phpnuxbill/master/install/radius.sql\">here</a> and import it to database.<br><br>Check config.php for radius connection details");
                    $ui->display('router-error.tpl');
                    die();
                }
            }
             // Save all settings including tax system
            $enable_session_timeout = isset($_POST['enable_session_timeout']) ? 1 : 0;
            $_POST['enable_session_timeout'] = $enable_session_timeout;
            foreach ($_POST as $key => $value) {
                $d = ORM::for_table('tbl_appconfig')->where('setting', $key)->find_one();
                if ($d) {
                    $d->value = $value;
                    $d->save();
                } else {
                    $d = ORM::for_table('tbl_appconfig')->create();
                    $d->setting = $key;
                    $d->value = $value;
                    $d->save();
                }
            }
            //checkbox
            $checks = ['hide_mrc', 'hide_tms', 'hide_aui', 'hide_al', 'hide_uet', 'hide_vs', 'hide_pg'];
            foreach ($checks as $check) {
                if (!isset($_POST[$check])) {
                    $d = ORM::for_table('tbl_appconfig')->where('setting', $check)->find_one();
                    if ($d) {
                        $d->value = 'no';
                        $d->save();
                    } else {
                        $d = ORM::for_table('tbl_appconfig')->create();
                        $d->setting = $check;
                        $d->value = 'no';
                        $d->save();
                    }
                }
            }

            _log('[' . $admin['username'] . ']: ' . Lang::T('Settings Saved Successfully'), $admin['user_type'], $admin['id']);

            r2(U . 'settings/app', 's', Lang::T('Settings Saved Successfully'));
        }
        break;

    case 'localisation':
        if (!in_array($admin['user_type'], ['SuperAdmin', 'Admin'])) {
            _alert(Lang::T('You do not have permission to access this page'), 'danger', "dashboard");
        }
        $folders = [];
        $files = scandir('system/lan/');
        foreach ($files as $file) {
            if (is_file('system/lan/' . $file) && !in_array($file, ['index.html', 'country.json', '.DS_Store'])) {
                $file = str_replace(".json", "", $file);
                $folders[$file] = '';
            }
        }
        $ui->assign('lani', $folders);
        $lans = Lang::getIsoLang();
        foreach ($lans as $lan => $val) {
            if (isset($folders[$lan])) {
                unset($lans[$lan]);
            }
        }
        $ui->assign('lan', $lans);
        $timezonelist = Timezone::timezoneList();
        $ui->assign('tlist', $timezonelist);
        $ui->assign('xjq', ' $("#tzone").select2(); ');
        run_hook('view_localisation'); #HOOK
        $ui->display('app-localisation.tpl');
        break;

    case 'localisation-post':
        if (!in_array($admin['user_type'], ['SuperAdmin', 'Admin'])) {
            _alert(Lang::T('You do not have permission to access this page'), 'danger', "dashboard");
        }
        $tzone = _post('tzone');
        $date_format = _post('date_format');
        $country_code_phone = _post('country_code_phone');
        $lan = _post('lan');
        run_hook('save_localisation'); #HOOK
        if ($tzone == '' or $date_format == '' or $lan == '') {
            r2(U . 'settings/app', 'e', Lang::T('All field is required'));
        } else {
            $d = ORM::for_table('tbl_appconfig')->where('setting', 'timezone')->find_one();
            $d->value = $tzone;
            $d->save();

            $d = ORM::for_table('tbl_appconfig')->where('setting', 'date_format')->find_one();
            $d->value = $date_format;
            $d->save();

            $dec_point = $_POST['dec_point'];
            if (strlen($dec_point) == '1') {
                $d = ORM::for_table('tbl_appconfig')->where('setting', 'dec_point')->find_one();
                $d->value = $dec_point;
                $d->save();
            }

            $thousands_sep = $_POST['thousands_sep'];
            if (strlen($thousands_sep) == '1') {
                $d = ORM::for_table('tbl_appconfig')->where('setting', 'thousands_sep')->find_one();
                $d->value = $thousands_sep;
                $d->save();
            }

            $d = ORM::for_table('tbl_appconfig')->where('setting', 'country_code_phone')->find_one();
            if ($d) {
                $d->value = $country_code_phone;
                $d->save();
            } else {
                $d = ORM::for_table('tbl_appconfig')->create();
                $d->setting = 'country_code_phone';
                $d->value = $country_code_phone;
                $d->save();
            }

            $d = ORM::for_table('tbl_appconfig')->where('setting', 'radius_plan')->find_one();
            if ($d) {
                $d->value = _post('radius_plan');
                $d->save();
            } else {
                $d = ORM::for_table('tbl_appconfig')->create();
                $d->setting = 'radius_plan';
                $d->value = _post('radius_plan');
                $d->save();
            }
            $d = ORM::for_table('tbl_appconfig')->where('setting', 'hotspot_plan')->find_one();
            if ($d) {
                $d->value = _post('hotspot_plan');
                $d->save();
            } else {
                $d = ORM::for_table('tbl_appconfig')->create();
                $d->setting = 'hotspot_plan';
                $d->value = _post('hotspot_plan');
                $d->save();
            }
            $d = ORM::for_table('tbl_appconfig')->where('setting', 'pppoe_plan')->find_one();
            if ($d) {
                $d->value = _post('pppoe_plan');
                $d->save();
            } else {
                $d = ORM::for_table('tbl_appconfig')->create();
                $d->setting = 'pppoe_plan';
                $d->value = _post('pppoe_plan');
                $d->save();
            }

            $currency_code = $_POST['currency_code'];
            $d = ORM::for_table('tbl_appconfig')->where('setting', 'currency_code')->find_one();
            $d->value = $currency_code;
            $d->save();

            $d = ORM::for_table('tbl_appconfig')->where('setting', 'language')->find_one();
            $d->value = $lan;
            $d->save();
            unset($_SESSION['Lang']);
            _log('[' . $admin['username'] . ']: ' . 'Settings Saved Successfully', $admin['user_type'], $admin['id']);
            r2(U . 'settings/localisation', 's', 'Settings Saved Successfully');
        }
        break;

    case 'users':
        if (!in_array($admin['user_type'], ['SuperAdmin', 'Admin', 'Agent'])) {
            _alert(Lang::T('You do not have permission to access this page'), 'danger', "dashboard");
        }
        $search = _req('search');
        if ($search != '') {
            if ($admin['user_type'] == 'SuperAdmin') {
                $query = ORM::for_table('tbl_users')
                    ->where_like('username', '%' . $search . '%')
                    ->order_by_asc('id');
                $d = Paginator::findMany($query, ['search' => $search]);
            } else if ($admin['user_type'] == 'Admin') {
                $query = ORM::for_table('tbl_users')
                    ->where_like('username', '%' . $search . '%')->where_any_is([
                        ['user_type' => 'Report'],
                        ['user_type' => 'Agent'],
                        ['user_type' => 'Sales'],
                        ['id' => $admin['id']]
                    ])->order_by_asc('id');
                $d = Paginator::findMany($query, ['search' => $search]);
            } else {
                $query = ORM::for_table('tbl_users')
                    ->where_like('username', '%' . $search . '%')
                    ->where_any_is([
                        ['id' => $admin['id']],
                        ['root' => $admin['id']]
                    ])->order_by_asc('id');
                $d = Paginator::findMany($query, ['search' => $search]);
            }
        } else {
            if ($admin['user_type'] == 'SuperAdmin') {
                $query = ORM::for_table('tbl_users')->order_by_asc('id');
                $d = Paginator::findMany($query);
            } else if ($admin['user_type'] == 'Admin') {
                $query = ORM::for_table('tbl_users')->where_any_is([
                    ['user_type' => 'Report'],
                    ['user_type' => 'Agent'],
                    ['user_type' => 'Sales'],
                    ['id' => $admin['id']]
                ])->order_by_asc('id');
                $d = Paginator::findMany($query);
            } else {
                $query = ORM::for_table('tbl_users')
                    ->where_any_is([
                        ['id' => $admin['id']],
                        ['root' => $admin['id']]
                    ])->order_by_asc('id');
                $d = Paginator::findMany($query);
            }
        }
        $admins = [];
        foreach ($d as $k) {
            if (!empty($k['root'])) {
                $admins[] = $k['root'];
            }
        }
        if (count($admins) > 0) {
            $adms = ORM::for_table('tbl_users')->where_in('id', $admins)->findArray();
            unset($admins);
            foreach ($adms as $adm) {
                $admins[$adm['id']] = $adm['fullname'];
            }
        }
        $ui->assign('admins', $admins);
        $ui->assign('d', $d);
        $ui->assign('search', $search);
        run_hook('view_list_admin'); #HOOK
        $ui->display('users.tpl');
        break;

    case 'users-add':
        if (!in_array($admin['user_type'], ['SuperAdmin', 'Admin', 'Agent'])) {
            _alert(Lang::T('You do not have permission to access this page'), 'danger', "dashboard");
        }
        $ui->assign('_title', Lang::T('Add User'));
        $ui->assign('agents', ORM::for_table('tbl_users')->where('user_type', 'Agent')->find_many());
        $ui->display('users-add.tpl');
        break;
    case 'users-view':
        $ui->assign('_title', Lang::T('Edit User'));
        $id  = $routes['2'];
        if (empty($id)) {
            $id = $admin['id'];
        }
        //allow see himself
        if ($admin['id'] == $id) {
            $d = ORM::for_table('tbl_users')->where('id', $id)->find_array($id)[0];
        } else {
            if (in_array($admin['user_type'], ['SuperAdmin', 'Admin'])) {
                // Super Admin can see anyone
                $d = ORM::for_table('tbl_users')->where('id', $id)->find_array()[0];
            } else if ($admin['user_type'] == 'Agent') {
                // Agent can see Sales
                $d = ORM::for_table('tbl_users')->where_any_is([['root' => $admin['id']], ['id' => $id]])->find_array()[0];
            }
        }
        if ($d) {
            run_hook('view_edit_admin'); #HOOK
            if ($d['user_type'] == 'Sales') {
                $ui->assign('agent', ORM::for_table('tbl_users')->where('id', $d['root'])->find_array()[0]);
            }
            $ui->assign('d', $d);
            $ui->assign('_title', $d['username']);
            $ui->display('users-view.tpl');
        } else {
            r2(U . 'settings/users', 'e', Lang::T('Account Not Found'));
        }
        break;
    case 'users-edit':
        if (!in_array($admin['user_type'], ['SuperAdmin', 'Admin', 'Agent'])) {
            _alert(Lang::T('You do not have permission to access this page'), 'danger', "dashboard");
        }
        $ui->assign('_title', Lang::T('Edit User'));
        $id  = $routes['2'];
        if (empty($id)) {
            $id = $admin['id'];
        }
        if ($admin['id'] == $id) {
            $d = ORM::for_table('tbl_users')->find_one($id);
        } else {
            if ($admin['user_type'] == 'SuperAdmin') {
                $d = ORM::for_table('tbl_users')->find_one($id);
                $ui->assign('agents', ORM::for_table('tbl_users')->where('user_type', 'Agent')->find_many());
            } else if ($admin['user_type'] == 'Admin') {
                $d = ORM::for_table('tbl_users')->where_any_is([
                    ['user_type' => 'Report'],
                    ['user_type' => 'Agent'],
                    ['user_type' => 'Sales']
                ])->find_one($id);
                $ui->assign('agents', ORM::for_table('tbl_users')->where('user_type', 'Agent')->find_many());
            } else {
                // Agent cannot move Sales to other Agent
                $ui->assign('agents', ORM::for_table('tbl_users')->where('id', $admin['id'])->find_many());
                $d = ORM::for_table('tbl_users')->where('root', $admin['id'])->find_one($id);
            }
        }
        if ($d) {
            $ui->assign('id', $id);
            $ui->assign('d', $d);
            run_hook('view_edit_admin'); #HOOK
            $ui->display('users-edit.tpl');
        } else {
            r2(U . 'settings/users', 'e', Lang::T('Account Not Found'));
        }
        break;

    case 'users-delete':
        if (!in_array($admin['user_type'], ['SuperAdmin', 'Admin'])) {
            _alert(Lang::T('You do not have permission to access this page'), 'danger', "dashboard");
        }

        $id  = $routes['2'];
        if (($admin['id']) == $id) {
            r2(U . 'settings/users', 'e', 'Sorry You can\'t delete yourself');
        }
        $d = ORM::for_table('tbl_users')->find_one($id);
        if ($d) {
            run_hook('delete_admin'); #HOOK
            $d->delete();
            r2(U . 'settings/users', 's', Lang::T('User deleted Successfully'));
        } else {
            r2(U . 'settings/users', 'e', Lang::T('Account Not Found'));
        }
        break;

    case 'users-post':
        if (!in_array($admin['user_type'], ['SuperAdmin', 'Admin', 'Agent'])) {
            _alert(Lang::T('You do not have permission to access this page'), 'danger', "dashboard");
        }
        $username = _post('username');
        $fullname = _post('fullname');
        $password = _post('password');
        $user_type = _post('user_type');
        $phone = _post('phone');
        $email = _post('email');
        $city = _post('city');
        $subdistrict = _post('subdistrict');
        $ward = _post('ward');
        $send_notif = _post('send_notif');
        $root = _post('root');
        $msg = '';
        if (Validator::Length($username, 45, 2) == false) {
            $msg .= Lang::T('Username should be between 3 to 45 characters') . '<br>';
        }
        if (Validator::Length($fullname, 45, 2) == false) {
            $msg .= Lang::T('Full Name should be between 3 to 45 characters') . '<br>';
        }
        if (!Validator::Length($password, 1000, 5)) {
            $msg .= Lang::T('Password should be minimum 6 characters') . '<br>';
        }

        $d = ORM::for_table('tbl_users')->where('username', $username)->find_one();
        if ($d) {
            $msg .= Lang::T('Account already axist') . '<br>';
        }
        $date_now = date("Y-m-d H:i:s");
        run_hook('add_admin'); #HOOK
        if ($msg == '') {
            $passwordC = Password::_crypt($password);
            $d = ORM::for_table('tbl_users')->create();
            $d->username = $username;
            $d->fullname = $fullname;
            $d->password = $passwordC;
            $d->user_type = $user_type;
            $d->phone = $phone;
            $d->email = $email;
            $d->city = $city;
            $d->subdistrict = $subdistrict;
            $d->ward = $ward;
            $d->status = 'Active';
            $d->creationdate = $date_now;
            if ($admin['user_type'] == 'Agent') {
                // Prevent hacking from form
                $d->root = $admin['id'];
            } else if ($user_type == 'Sales') {
                $d->root = $root;
            }
            $d->save();

            if ($send_notif == 'wa') {
                Message::sendWhatsapp(Lang::phoneFormat($phone), Lang::T('Hello, Your account has been created successfully.') . "\nUsername: $username\nPassword: $password\n\n" . $config['CompanyName']);
            } else if ($send_notif == 'sms') {
                Message::sendSMS($phone, Lang::T('Hello, Your account has been created successfully.') . "\nUsername: $username\nPassword: $password\n\n" . $config['CompanyName']);
            }

            _log('[' . $admin['username'] . ']: ' . "Created $user_type <b>$username</b>", $admin['user_type'], $admin['id']);
            r2(U . 'settings/users', 's', Lang::T('Account Created Successfully'));
        } else {
            r2(U . 'settings/users-add', 'e', $msg);
        }
        break;

    case 'users-edit-post':
        $username = _post('username');
        $fullname = _post('fullname');
        $password = _post('password');
        $cpassword = _post('cpassword');
        $user_type = _post('user_type');
        $phone = _post('phone');
        $email = _post('email');
        $city = _post('city');
        $subdistrict = _post('subdistrict');
        $ward = _post('ward');
        $status = _post('status');
        $root = _post('root');
        $msg = '';
        if (Validator::Length($username, 45, 2) == false) {
            $msg .= Lang::T('Username should be between 3 to 45 characters') . '<br>';
        }
        if (Validator::Length($fullname, 45, 2) == false) {
            $msg .= Lang::T('Full Name should be between 3 to 45 characters') . '<br>';
        }
        if ($password != '') {
            if (!Validator::Length($password, 1000, 5)) {
                $msg .= Lang::T('Password should be minimum 6 characters') . '<br>';
            }
            if ($password != $cpassword) {
                $msg .= Lang::T('Passwords does not match') . '<br>';
            }
        }

        $id = _post('id');
        if ($admin['id'] == $id) {
            $d = ORM::for_table('tbl_users')->find_one($id);
        } else {
            if ($admin['user_type'] == 'SuperAdmin') {
                $d = ORM::for_table('tbl_users')->find_one($id);
            } else if ($admin['user_type'] == 'Admin') {
                $d = ORM::for_table('tbl_users')->where_any_is([
                    ['user_type' => 'Report'],
                    ['user_type' => 'Agent'],
                    ['user_type' => 'Sales']
                ])->find_one($id);
            } else {
                $d = ORM::for_table('tbl_users')->where('root', $admin['id'])->find_one($id);
            }
        }
        if (!$d) {
            $msg .= Lang::T('Data Not Found') . '<br>';
        }

        if ($d['username'] != $username) {
            $c = ORM::for_table('tbl_users')->where('username', $username)->find_one();
            if ($c) {
                $msg .= "<b>$username</b> " . Lang::T('Account already axist') . '<br>';
            }
        }
        run_hook('edit_admin'); #HOOK
        if ($msg == '') {
            $d->username = $username;
            if ($password != '') {
                $password = Password::_crypt($password);
                $d->password = $password;
            }

            $d->fullname = $fullname;
            if (($admin['id']) != $id) {
                $user_type = _post('user_type');
                $d->user_type = $user_type;
            }
            $d->phone = $phone;
            $d->email = $email;
            $d->city = $city;
            $d->subdistrict = $subdistrict;
            $d->ward = $ward;
            if (isset($_POST['status'])) {
                $d->status = $status;
            }

            if ($admin['user_type'] == 'Agent') {
                // Prevent hacking from form
                $d->root = $admin['id'];
            } else if ($user_type == 'Sales') {
                $d->root = $root;
            }

            $d->save();

            _log('[' . $admin['username'] . ']: $username ' . Lang::T('User Updated Successfully'), $admin['user_type'], $admin['id']);
            r2(U . 'settings/users', 's', 'User Updated Successfully');
        } else {
            r2(U . 'settings/users-edit/' . $id, 'e', $msg);
        }
        break;

    case 'change-password':
        run_hook('view_change_password'); #HOOK
        $ui->display('change-password.tpl');
        break;

    case 'change-password-post':
        $password = _post('password');
        if ($password != '') {
            $d = ORM::for_table('tbl_users')->where('username', $admin['username'])->find_one();
            run_hook('change_password'); #HOOK
            if ($d) {
                $d_pass = $d['password'];
                if (Password::_verify($password, $d_pass) == true) {
                    $npass = _post('npass');
                    $cnpass = _post('cnpass');
                    if (!Validator::Length($npass, 15, 5)) {
                        r2(U . 'settings/change-password', 'e', 'New Password must be 6 to 14 character');
                    }
                    if ($npass != $cnpass) {
                        r2(U . 'settings/change-password', 'e', 'Both Password should be same');
                    }

                    $npass = Password::_crypt($npass);
                    $d->password = $npass;
                    $d->save();

                    _msglog('s', Lang::T('Password changed successfully, Please login again'));
                    _log('[' . $admin['username'] . ']: Password changed successfully', $admin['user_type'], $admin['id']);

                    r2(U . 'admin');
                } else {
                    r2(U . 'settings/change-password', 'e', Lang::T('Incorrect Current Password'));
                }
            } else {
                r2(U . 'settings/change-password', 'e', Lang::T('Incorrect Current Password'));
            }
        } else {
            r2(U . 'settings/change-password', 'e', Lang::T('Incorrect Current Password'));
        }
        break;

    case 'notifications':
        if (!in_array($admin['user_type'], ['SuperAdmin', 'Admin'])) {
            _alert(Lang::T('You do not have permission to access this page'), 'danger', "dashboard");
        }
        run_hook('view_notifications'); #HOOK
        if (file_exists($UPLOAD_PATH . DIRECTORY_SEPARATOR . "notifications.json")) {
            $ui->assign('_json', json_decode(file_get_contents($UPLOAD_PATH . DIRECTORY_SEPARATOR . 'notifications.json'), true));
        } else {
            $ui->assign('_json', json_decode(file_get_contents($UPLOAD_PATH . DIRECTORY_SEPARATOR . 'notifications.default.json'), true));
        }
        $ui->assign('_default', json_decode(file_get_contents($UPLOAD_PATH . DIRECTORY_SEPARATOR . 'notifications.default.json'), true));
        $ui->display('app-notifications.tpl');
        break;
    case 'notifications-post':
        if (!in_array($admin['user_type'], ['SuperAdmin', 'Admin'])) {
            _alert(Lang::T('You do not have permission to access this page'), 'danger', "dashboard");
        }
        file_put_contents($UPLOAD_PATH . "/notifications.json", json_encode($_POST));
        r2(U . 'settings/notifications', 's', Lang::T('Settings Saved Successfully'));
        break;
    case 'dbstatus':
        if (!in_array($admin['user_type'], ['SuperAdmin', 'Admin'])) {
            _alert(Lang::T('You do not have permission to access this page'), 'danger', "dashboard");
        }

        $dbc = new mysqli($db_host, $db_user, $db_pass, $db_name);
        if ($result = $dbc->query('SHOW TABLE STATUS')) {
            $tables = array();
            while ($row = $result->fetch_array()) {
                $tables[$row['Name']]['rows'] = ORM::for_table($row["Name"])->count();
                $tables[$row['Name']]['name'] = $row["Name"];
            }
            $ui->assign('tables', $tables);
            run_hook('view_database'); #HOOK
            $ui->display('dbstatus.tpl');
        }
        break;

    case 'dbbackup':
        if (!in_array($admin['user_type'], ['SuperAdmin'])) {
            _alert(Lang::T('You do not have permission to access this page'), 'danger', "dashboard");
        }
        $tables = $_POST['tables'];
        set_time_limit(-1);
        header('Pragma: public');
        header('Expires: 0');
        header('Cache-Control: must-revalidate, post-check=0, pre-check=0');
        header('Content-Type: application/force-download');
        header('Content-Type: application/octet-stream');
        header('Content-Type: application/download');
        header('Content-Disposition: attachment;filename="phpnuxbill_' . count($tables) . '_tables_' . date('Y-m-d_H_i') . '.json"');
        header('Content-Transfer-Encoding: binary');
        $array = [];
        foreach ($tables as $table) {
            $array[$table] = ORM::for_table($table)->find_array();
        }
        echo json_encode($array);
        break;
    case 'dbrestore':
        if (!in_array($admin['user_type'], ['SuperAdmin'])) {
            _alert(Lang::T('You do not have permission to access this page'), 'danger', "dashboard");
        }
        if (file_exists($_FILES['json']['tmp_name'])) {
            $suc = 0;
            $fal = 0;
            $json = json_decode(file_get_contents($_FILES['json']['tmp_name']), true);
            try {
                ORM::raw_execute("SET FOREIGN_KEY_CHECKS=0;");
            } catch (Throwable $e) {
            } catch (Exception $e) {
            }
            try {
                ORM::raw_execute("SET GLOBAL FOREIGN_KEY_CHECKS=0;");
            } catch (Throwable $e) {
            } catch (Exception $e) {
            }
            foreach ($json as $table => $records) {
                ORM::raw_execute("TRUNCATE $table;");
                foreach ($records as $rec) {
                    try {
                        $t = ORM::for_table($table)->create();
                        foreach ($rec as $k => $v) {
                            $t->set($k, $v);
                        }
                        if ($t->save()) {
                            $suc++;
                        } else {
                            $fal++;
                        }
                    } catch (Throwable $e) {
                        $fal++;
                    } catch (Exception $e) {
                        $fal++;
                    }
                }
            }
            try {
                ORM::raw_execute("SET FOREIGN_KEY_CHECKS=1;");
            } catch (Throwable $e) {
            } catch (Exception $e) {
            }
            try {
                ORM::raw_execute("SET GLOBAL FOREIGN_KEY_CHECKS=1;");
            } catch (Throwable $e) {
            } catch (Exception $e) {
            }
            if (file_exists($_FILES['json']['tmp_name'])) unlink($_FILES['json']['tmp_name']);
            r2(U . "settings/dbstatus", 's', "Restored $suc success $fal failed");
        } else {
            r2(U . "settings/dbstatus", 'e', 'Upload failed');
        }
        break;
    case 'language':
        if (!in_array($admin['user_type'], ['SuperAdmin', 'Admin'])) {
            _alert(Lang::T('You do not have permission to access this page'), 'danger', "dashboard");
        }
        run_hook('view_add_language'); #HOOK
        if (file_exists($lan_file)) {
            $ui->assign('langs', json_decode(file_get_contents($lan_file), true));
        } else {
            $ui->assign('langs', []);
        }
        $ui->display('language-add.tpl');
        break;

    case 'lang-post':
        file_put_contents($lan_file, json_encode($_POST, JSON_PRETTY_PRINT));
        r2(U . 'settings/language', 's', Lang::T('Translation saved Successfully'));
        break;

    case 'maintenance':
        if (!in_array($admin['user_type'], ['SuperAdmin', 'Admin'])) {
            _alert(Lang::T('You do not have permission to access this page'), 'danger', "dashboard");
            exit;
        }
        if (_post('save') == 'save') {
            $status = isset($_POST['maintenance_mode']) ? 1 : 0; // Checkbox returns 1 if checked, otherwise 0
            $force_logout = isset($_POST['maintenance_mode_logout']) ? 1 : 0; // Checkbox returns 1 if checked, otherwise 0
            $date = isset($_POST['maintenance_date']) ? $_POST['maintenance_date'] : null;

            $settings = [
                'maintenance_mode' => $status,
                'maintenance_mode_logout' => $force_logout,
                'maintenance_date' => $date
            ];

            foreach ($settings as $key => $value) {
                $d = ORM::for_table('tbl_appconfig')->where('setting', $key)->find_one();
                if ($d) {
                    $d->value = $value;
                    $d->save();
                } else {
                    $d = ORM::for_table('tbl_appconfig')->create();
                    $d->setting = $key;
                    $d->value = $value;
                    $d->save();
                }
            }

            r2(U . "settings/maintenance", 's', Lang::T('Settings Saved Successfully'));
        }
        $ui->assign('_c', $config);
        $ui->assign('_title', Lang::T('Maintenance Mode Settings'));
        $ui->display('maintenance-mode.tpl');
        break;

    default:
        $ui->display('a404.tpl');
}
