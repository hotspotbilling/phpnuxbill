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
        r2(APP_URL . '/docs');
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
                if (method_exists($dvc, 'description')) {
                    $arr = $dvc->description();
                    $arr['file'] = $dev;
                    $devices[] = $arr;
                } else {
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
        $ui->display('admin/settings/devices.tpl');
        break;
    case 'app':
        if (!in_array($admin['user_type'], ['SuperAdmin', 'Admin'])) {
            _alert(Lang::T('You do not have permission to access this page'), 'danger', "dashboard");
        }

        if (!empty(_get('testWa'))) {
            if ($_app_stage == 'Demo') {
                r2(getUrl('settings/app'), 'e', 'You cannot perform this action in Demo mode');
            }
            $result = Message::sendWhatsapp(_get('testWa'), 'PHPNuxBill Test Whatsapp');
            r2(getUrl('settings/app'), 's', 'Test Whatsapp has been send<br>Result: ' . $result);
        }
        if (!empty(_get('testSms'))) {
            if ($_app_stage == 'Demo') {
                r2(getUrl('settings/app'), 'e', 'You cannot perform this action in Demo mode');
            }
            $result = Message::sendSMS(_get('testSms'), 'PHPNuxBill Test SMS');
            r2(getUrl('settings/app'), 's', 'Test SMS has been send<br>Result: ' . $result);
        }
        if (!empty(_get('testEmail'))) {
            if ($_app_stage == 'Demo') {
                r2(getUrl('settings/app'), 'e', 'You cannot perform this action in Demo mode');
            }
            Message::sendEmail(_get('testEmail'), 'PHPNuxBill Test Email', 'PHPNuxBill Test Email Body');
            r2(getUrl('settings/app'), 's', 'Test Email has been send');
        }
        if (!empty(_get('testTg'))) {
            if ($_app_stage == 'Demo') {
                r2(getUrl('settings/app'), 'e', 'You cannot perform this action in Demo mode');
            }
            $result = Message::sendTelegram('PHPNuxBill Test Telegram');
            r2(getUrl('settings/app'), 's', 'Test Telegram has been send<br>Result: ' . $result);
        }

        $UPLOAD_URL_PATH = str_replace($root_path, '', $UPLOAD_PATH);
        if (file_exists($UPLOAD_PATH . DIRECTORY_SEPARATOR . 'logo.png')) {
            $logo = $UPLOAD_URL_PATH . DIRECTORY_SEPARATOR . 'logo.png?' . time();
        } else {
            $logo = $UPLOAD_URL_PATH . DIRECTORY_SEPARATOR . 'logo.default.png';
        }
        $ui->assign('logo', $logo);

        if (!empty($config['login_page_logo']) && file_exists($UPLOAD_URL_PATH . DIRECTORY_SEPARATOR . $config['login_page_logo'])) {
            $login_logo = $UPLOAD_URL_PATH . DIRECTORY_SEPARATOR . $config['login_page_logo'];
        } elseif (file_exists($UPLOAD_URL_PATH . DIRECTORY_SEPARATOR . 'login-logo.png')) {
            $login_logo = $UPLOAD_URL_PATH . DIRECTORY_SEPARATOR . 'login-logo.png';
        } else {
            $login_logo = $UPLOAD_URL_PATH . DIRECTORY_SEPARATOR . 'login-logo.default.png';
        }

        if (!empty($config['login_page_wallpaper']) && file_exists($UPLOAD_URL_PATH . DIRECTORY_SEPARATOR . $config['login_page_wallpaper'])) {
            $wallpaper = $UPLOAD_URL_PATH . DIRECTORY_SEPARATOR . $config['login_page_wallpaper'];
        } elseif (file_exists($UPLOAD_URL_PATH . DIRECTORY_SEPARATOR . 'wallpaper.png')) {
            $wallpaper = $UPLOAD_URL_PATH . DIRECTORY_SEPARATOR . 'wallpaper.png';
        } else {
            $wallpaper = $UPLOAD_URL_PATH . DIRECTORY_SEPARATOR . 'wallpaper.default.png';
        }

        if (!empty($config['login_page_favicon']) && file_exists($UPLOAD_URL_PATH . DIRECTORY_SEPARATOR . $config['login_page_favicon'])) {
            $favicon = $UPLOAD_URL_PATH . DIRECTORY_SEPARATOR . $config['login_page_favicon'];
        } elseif (file_exists($UPLOAD_URL_PATH . DIRECTORY_SEPARATOR . 'favicon.png')) {
            $favicon = $UPLOAD_URL_PATH . DIRECTORY_SEPARATOR . 'favicon.png';
        } else {
            $favicon = $UPLOAD_URL_PATH . DIRECTORY_SEPARATOR . 'favicon.default.png';
        }

        $ui->assign('login_logo', $login_logo);
        $ui->assign('wallpaper', $wallpaper);
        $ui->assign('favicon', $favicon);

        $themes = [];
        $files = scandir('ui/themes/');
        foreach ($files as $file) {
            if (is_dir('ui/themes/' . $file) && !in_array($file, ['.', '..'])) {
                $themes[] = $file;
            }
        }

        $template_files = glob('ui/ui/customer/login-custom-*.tpl');
        $templates = [];

        foreach ($template_files as $file) {
            $parts = explode('-', basename($file, '.tpl'));
            $template_identifier = $parts[2] ?? 'unknown';
            $templates[] = [
                'filename' => basename($file),
                'value' => $template_identifier,
                'name' => str_replace('_', ' ', ucfirst($template_identifier))
            ];
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
        if (empty($config['mikrotik_sms_command'])) {
            $config['mikrotik_sms_command'] = "/tool sms send";
        }
        $ui->assign('template_files', $templates);
        $ui->assign('_c', $config);
        $ui->assign('php', $php);
        $ui->assign('dir', str_replace('controllers', '', __DIR__));
        $ui->assign('themes', $themes);
        run_hook('view_app_settings'); #HOOK
        $csrf_token = Csrf::generateAndStoreToken();
        $ui->assign('csrf_token', $csrf_token);
        $ui->display('admin/settings/app.tpl');
        break;

    case 'app-post':

        if ($_app_stage == 'Demo') {
            r2(getUrl('settings/app'), 'e', 'You cannot perform this action in Demo mode');
        }

        if (!in_array($admin['user_type'], ['SuperAdmin', 'Admin'])) {
            _alert(Lang::T('You do not have permission to access this page'), 'danger', "dashboard");
        }
        $csrf_token = _post('csrf_token');

        if (!Csrf::check($csrf_token)) {
            r2(getUrl('settings/app'), 'e', Lang::T('Invalid or Expired CSRF Token') . ".");
        }
        $company = _post('CompanyName');
        $custom_tax_rate = filter_var(_post('custom_tax_rate'), FILTER_SANITIZE_SPECIAL_CHARS);
        if (preg_match('/[^0-9.]/', $custom_tax_rate)) {
            r2(getUrl('settings/app'), 'e', 'Special characters are not allowed in tax rate');
            die();
        }
        run_hook('save_settings'); #HOOK
        if (!empty($_FILES['logo']['name'])) {
            if (function_exists('imagecreatetruecolor')) {
                if (file_exists($UPLOAD_PATH . DIRECTORY_SEPARATOR . 'logo.png'))
                    unlink($UPLOAD_PATH . DIRECTORY_SEPARATOR . 'logo.png');
                File::resizeCropImage($_FILES['logo']['tmp_name'], $UPLOAD_PATH . DIRECTORY_SEPARATOR . 'logo.png', 1078, 200, 100);
                if (file_exists($_FILES['logo']['tmp_name']))
                    unlink($_FILES['logo']['tmp_name']);
            } else {
                r2(getUrl('settings/app'), 'e', 'PHP GD is not installed');
            }
        }
        if ($_POST['general'] && $company == '') {
            r2(getUrl('settings/app'), 'e', Lang::T('All field is required'));
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
                    $ui->display('admin/error.tpl');
                    die();
                }
            }
            // Save all settings including tax system
            $_POST['man_fields_email'] = isset($_POST['man_fields_email']) ? 'yes' : 'no';
            $_POST['man_fields_fname'] = isset($_POST['man_fields_fname']) ? 'yes' : 'no';
            $_POST['man_fields_address'] = isset($_POST['man_fields_address']) ? 'yes' : 'no';
            $_POST['man_fields_custom'] = isset($_POST['man_fields_custom']) ? 'yes' : 'no';
            $enable_session_timeout = isset($_POST['enable_session_timeout']) ? 1 : 0;
            $_POST['enable_session_timeout'] = $enable_session_timeout;
            $_POST['notification_reminder_1day'] = isset($_POST['notification_reminder_1day']) ? 'yes' : 'no';
            $_POST['notification_reminder_3days'] = isset($_POST['notification_reminder_3days']) ? 'yes' : 'no';
            $_POST['notification_reminder_7days'] = isset($_POST['notification_reminder_7days']) ? 'yes' : 'no';

            // hide dashboard
            $_POST['hide_mrc'] = _post('hide_mrc', 'no');
            $_POST['hide_tms'] = _post('hide_tms', 'no');
            $_POST['hide_al'] = _post('hide_al', 'no');
            $_POST['hide_uet'] = _post('hide_uet', 'no');
            $_POST['hide_vs'] = _post('hide_vs', 'no');
            $_POST['hide_pg'] = _post('hide_pg', 'no');
            $_POST['hide_aui'] = _post('hide_aui', 'no');

            // Login page post
            $login_page_title = _post('login_page_head');
            $login_page_description = _post('login_page_description');
            $login_Page_template = _post('login_Page_template');
            $login_page_type = _post('login_page_type');
            $csrf_token = _post('csrf_token');

            if (!Csrf::check($csrf_token)) {
                r2(getUrl('settings/app'), 'e', Lang::T('Invalid or Expired CSRF Token') . ".");
            }

            if ($login_page_type == 'custom' && (empty($login_Page_template) || empty($login_page_title) || empty($login_page_description))) {
                r2(getUrl('settings/app'), 'e', 'Please fill all required fields');
                return;
            }

            if (strlen($login_page_title) > 25) {
                r2(getUrl('settings/app'), 'e', 'Login page title must not exceed 25 characters');
                return;
            }
            if (strlen($login_page_description) > 100) {
                r2(getUrl('settings/app'), 'e', 'Login page description must not exceed 50 characters');
                return;
            }

            $image_paths = [];
            $allowed_types = ['image/jpeg', 'image/png'];

            if ($_FILES['login_page_favicon']['name'] != '') {
                $favicon_type = $_FILES['login_page_favicon']['type'];
                if (in_array($favicon_type, $allowed_types) && preg_match('/\.(jpg|jpeg|png)$/i', $_FILES['login_page_favicon']['name'])) {
                    $extension = pathinfo($_FILES['login_page_favicon']['name'], PATHINFO_EXTENSION);
                    $favicon_path = $UPLOAD_PATH . DIRECTORY_SEPARATOR . uniqid('favicon_') . '.' . $extension;
                    File::resizeCropImage($_FILES['login_page_favicon']['tmp_name'], $favicon_path, 16, 16, 100);
                    $_POST['login_page_favicon'] = basename($favicon_path); // Save dynamic file name
                    if (file_exists($_FILES['login_page_favicon']['tmp_name']))
                        unlink($_FILES['login_page_favicon']['tmp_name']);
                } else {
                    r2(getUrl('settings/app'), 'e', 'Favicon must be a JPG, JPEG, or PNG image.');
                }
            }

            if ($_FILES['login_page_wallpaper']['name'] != '') {
                $wallpaper_type = $_FILES['login_page_wallpaper']['type'];
                if (in_array($wallpaper_type, $allowed_types) && preg_match('/\.(jpg|jpeg|png)$/i', $_FILES['login_page_wallpaper']['name'])) {
                    $extension = pathinfo($_FILES['login_page_wallpaper']['name'], PATHINFO_EXTENSION);
                    $wallpaper_path = $UPLOAD_PATH . DIRECTORY_SEPARATOR . uniqid('wallpaper_') . '.' . $extension;
                    File::resizeCropImage($_FILES['login_page_wallpaper']['tmp_name'], $wallpaper_path, 1920, 1080, 100);
                    $_POST['login_page_wallpaper'] = basename($wallpaper_path); // Save dynamic file name
                    if (file_exists($_FILES['login_page_wallpaper']['tmp_name']))
                        unlink($_FILES['login_page_wallpaper']['tmp_name']);
                } else {
                    r2(getUrl('settings/app'), 'e', 'Wallpaper must be a JPG, JPEG, or PNG image.');
                }
            }

            if ($_FILES['login_page_logo']['name'] != '') {
                $logo_type = $_FILES['login_page_logo']['type'];
                if (in_array($logo_type, $allowed_types) && preg_match('/\.(jpg|jpeg|png)$/i', $_FILES['login_page_logo']['name'])) {
                    $extension = pathinfo($_FILES['login_page_logo']['name'], PATHINFO_EXTENSION);
                    $logo_path = $UPLOAD_PATH . DIRECTORY_SEPARATOR . uniqid('logo_') . '.' . $extension;
                    File::resizeCropImage($_FILES['login_page_logo']['tmp_name'], $logo_path, 300, 60, 100);
                    $_POST['login_page_logo'] = basename($logo_path); // Save dynamic file name
                    if (file_exists($_FILES['login_page_logo']['tmp_name']))
                        unlink($_FILES['login_page_logo']['tmp_name']);
                } else {
                    r2(getUrl('settings/app'), 'e', 'Logo must be a JPG, JPEG, or PNG image.');
                }
            }
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
            _log('[' . $admin['username'] . ']: ' . Lang::T('Settings Saved Successfully'), $admin['user_type'], $admin['id']);

            r2(getUrl('settings/app'), 's', Lang::T('Settings Saved Successfully'));
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
        $csrf_token = Csrf::generateAndStoreToken();
        $ui->assign('csrf_token', $csrf_token);
        $ui->display('admin/settings/localisation.tpl');
        break;

    case 'localisation-post':
        if (!in_array($admin['user_type'], ['SuperAdmin', 'Admin'])) {
            _alert(Lang::T('You do not have permission to access this page'), 'danger', "dashboard");
        }
        if ($_app_stage == 'Demo') {
            r2(getUrl('settings/localisation'), 'e', 'You cannot perform this action in Demo mode');
        }
        $csrf_token = _post('csrf_token');
        if (!Csrf::check($csrf_token)) {
            r2(getUrl('settings/localisation'), 'e', Lang::T('Invalid or Expired CSRF Token') . ".");
        }
        $tzone = _post('tzone');
        $date_format = _post('date_format');
        $country_code_phone = _post('country_code_phone');
        $lan = _post('lan');
        run_hook('save_localisation'); #HOOK
        if ($tzone == '' or $date_format == '' or $lan == '') {
            r2(getUrl('settings/localisation'), 'e', Lang::T('All field is required'));
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
            $d = ORM::for_table('tbl_appconfig')->where('setting', 'vpn_plan')->find_one();
            if ($d) {
                $d->value = _post('vpn_plan');
                $d->save();
            } else {
                $d = ORM::for_table('tbl_appconfig')->create();
                $d->setting = 'vpn_plan';
                $d->value = _post('vpn_plan');
                $d->save();
            }

            $currency_code = $_POST['currency_code'];
            $d = ORM::for_table('tbl_appconfig')->where('setting', 'currency_code')->find_one();
            $d->value = $currency_code;
            $d->save();

            $d = ORM::for_table('tbl_appconfig')->where('setting', 'language')->find_one();
            $d->value = $lan;
            $d->save();
            _log('[' . $admin['username'] . ']: ' . 'Settings Saved Successfully', $admin['user_type'], $admin['id']);
            r2(getUrl('settings/localisation'), 's', 'Settings Saved Successfully');
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
        $csrf_token = Csrf::generateAndStoreToken();
        $ui->assign('csrf_token', $csrf_token);
        $ui->display('admin/admin/list.tpl');
        break;

    case 'users-add':
        if (!in_array($admin['user_type'], ['SuperAdmin', 'Admin', 'Agent'])) {
            _alert(Lang::T('You do not have permission to access this page'), 'danger', "dashboard");
        }
        $csrf_token = Csrf::generateAndStoreToken();
        $ui->assign('csrf_token', $csrf_token);
        $ui->assign('_title', Lang::T('Add User'));
        $ui->assign('agents', ORM::for_table('tbl_users')->where('user_type', 'Agent')->find_many());
        $ui->display('admin/admin/add.tpl');
        break;
    case 'users-view':
        $ui->assign('_title', Lang::T('Edit User'));
        $id = $routes['2'];
        if (empty($id)) {
            $id = $admin['id'];
        }
        //allow see himself
        if ($admin['id'] == $id) {
            $d = ORM::for_table('tbl_users')->where('id', $id)->find_array()[0];
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
            $csrf_token = Csrf::generateAndStoreToken();
            $ui->assign('csrf_token', $csrf_token);
            $ui->display('admin/admin/view.tpl');
        } else {
            r2(getUrl('settings/users'), 'e', Lang::T('Account Not Found'));
        }
        break;
    case 'users-edit':
        if (!in_array($admin['user_type'], ['SuperAdmin', 'Admin', 'Agent'])) {
            _alert(Lang::T('You do not have permission to access this page'), 'danger', "dashboard");
        }
        $ui->assign('_title', Lang::T('Edit User'));
        $id = $routes['2'];
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
            if (isset($routes['3']) && $routes['3'] == 'deletePhoto') {
                if ($d['photo'] != '' && strpos($d['photo'], 'default') === false) {
                    if (file_exists($UPLOAD_PATH . $d['photo']) && strpos($d['photo'], 'default') === false) {
                        unlink($UPLOAD_PATH . $d['photo']);
                        if (file_exists($UPLOAD_PATH . $d['photo'] . '.thumb.jpg')) {
                            unlink($UPLOAD_PATH . $d['photo'] . '.thumb.jpg');
                        }
                    }
                    $d->photo = '/admin.default.png';
                    $d->save();
                    $ui->assign('notify_t', 's');
                    $ui->assign('notify', 'You have successfully deleted the photo');
                } else {
                    $ui->assign('notify_t', 'e');
                    $ui->assign('notify', 'No photo found to delete');
                }
            }
            $ui->assign('id', $id);
            $ui->assign('d', $d);
            run_hook('view_edit_admin'); #HOOK
            $csrf_token = Csrf::generateAndStoreToken();
            $ui->assign('csrf_token', $csrf_token);
            $ui->display('admin/admin/edit.tpl');
        } else {
            r2(getUrl('settings/users'), 'e', Lang::T('Account Not Found'));
        }
        break;

    case 'users-delete':
        if (!in_array($admin['user_type'], ['SuperAdmin', 'Admin'])) {
            _alert(Lang::T('You do not have permission to access this page'), 'danger', "dashboard");
        }
        if ($_app_stage == 'Demo') {
            r2(getUrl('settings/users'), 'e', 'You cannot perform this action in Demo mode');
        }
        $id = $routes['2'];
        if (($admin['id']) == $id) {
            r2(getUrl('settings/users'), 'e', 'Sorry You can\'t delete yourself');
        }
        $d = ORM::for_table('tbl_users')->find_one($id);
        if ($d) {
            run_hook('delete_admin'); #HOOK
            $d->delete();
            r2(getUrl('settings/users'), 's', Lang::T('User deleted Successfully'));
        } else {
            r2(getUrl('settings/users'), 'e', Lang::T('Account Not Found'));
        }
        break;

    case 'users-post':
        if (!in_array($admin['user_type'], ['SuperAdmin', 'Admin', 'Agent'])) {
            _alert(Lang::T('You do not have permission to access this page'), 'danger', "dashboard");
        }
        if ($_app_stage == 'Demo') {
            r2(getUrl('settings/users-add'), 'e', 'You cannot perform this action in Demo mode');
        }
        $csrf_token = _post('csrf_token');
        if (!Csrf::check($csrf_token)) {
            r2(getUrl('settings/users-add'), 'e', Lang::T('Invalid or Expired CSRF Token') . ".");
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
            r2(getUrl('settings/users'), 's', Lang::T('Account Created Successfully'));
        } else {
            r2(getUrl('settings/users-add'), 'e', $msg);
        }
        break;

    case 'users-edit-post':
        if ($_app_stage == 'Demo') {
            r2(getUrl('settings/users-edit/'), 'e', 'You cannot perform this action in Demo mode');
        }
        $csrf_token = _post('csrf_token');
        if (!Csrf::check($csrf_token)) {
            r2(getUrl('settings/users-edit/'), 'e', Lang::T('Invalid or Expired CSRF Token') . ".");
        }
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
            if (!empty($_FILES['photo']['name']) && file_exists($_FILES['photo']['tmp_name'])) {
                if (function_exists('imagecreatetruecolor')) {
                    $hash = md5_file($_FILES['photo']['tmp_name']);
                    $subfolder = substr($hash, 0, 2);
                    $folder = $UPLOAD_PATH . DIRECTORY_SEPARATOR . 'photos' . DIRECTORY_SEPARATOR;
                    if (!file_exists($folder)) {
                        mkdir($folder);
                    }
                    $folder = $UPLOAD_PATH . DIRECTORY_SEPARATOR . 'photos' . DIRECTORY_SEPARATOR . $subfolder . DIRECTORY_SEPARATOR;
                    if (!file_exists($folder)) {
                        mkdir($folder);
                    }
                    $imgPath = $folder . $hash . '.jpg';
                    if (!file_exists($imgPath)) {
                        File::resizeCropImage($_FILES['photo']['tmp_name'], $imgPath, 1600, 1600, 100);
                    }
                    if (!file_exists($imgPath . '.thumb.jpg')) {
                        if (_post('faceDetect') == 'yes') {
                            try {
                                $detector = new svay\FaceDetector();
                                $detector->setTimeout(5000);
                                $detector->faceDetect($imgPath);
                                $detector->cropFaceToJpeg($imgPath . '.thumb.jpg', false);
                            } catch (Exception $e) {
                                File::makeThumb($imgPath, $imgPath . '.thumb.jpg', 200);
                            } catch (Throwable $e) {
                                File::makeThumb($imgPath, $imgPath . '.thumb.jpg', 200);
                            }
                        } else {
                            File::makeThumb($imgPath, $imgPath . '.thumb.jpg', 200);
                        }
                    }
                    if (file_exists($imgPath)) {
                        if ($d['photo'] != '' && strpos($d['photo'], 'default') === false) {
                            if (file_exists($UPLOAD_PATH . $d['photo'])) {
                                unlink($UPLOAD_PATH . $d['photo']);
                                if (file_exists($UPLOAD_PATH . $d['photo'] . '.thumb.jpg')) {
                                    unlink($UPLOAD_PATH . $d['photo'] . '.thumb.jpg');
                                }
                            }
                        }
                        $d->photo = '/photos/' . $subfolder . '/' . $hash . '.jpg';
                    }
                    if (file_exists($_FILES['photo']['tmp_name']))
                        unlink($_FILES['photo']['tmp_name']);
                } else {
                    r2(getUrl('settings/app'), 'e', 'PHP GD is not installed');
                }
            }

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
            r2(getUrl('settings/users-view/') . $id, 's', 'User Updated Successfully');
        } else {
            r2(getUrl('settings/users-edit/') . $id, 'e', $msg);
        }
        break;

    case 'change-password':
        run_hook('view_change_password'); #HOOK
        $csrf_token = Csrf::generateAndStoreToken();
        $ui->assign('csrf_token', $csrf_token);
        $ui->display('admin/change-password.tpl');
        break;

    case 'change-password-post':
        if ($_app_stage == 'Demo') {
            r2(getUrl('settings/change-password'), 'e', 'You cannot perform this action in Demo mode');
        }
        $password = _post('password');
        $csrf_token = _post('csrf_token');
        if (!Csrf::check($csrf_token)) {
            r2(getUrl('settings/change-password'), 'e', Lang::T('Invalid or Expired CSRF Token') . ".");
        }
        if ($password != '') {
            $d = ORM::for_table('tbl_users')->where('username', $admin['username'])->find_one();
            run_hook('change_password'); #HOOK
            if ($d) {
                $d_pass = $d['password'];
                if (Password::_verify($password, $d_pass) == true) {
                    $npass = _post('npass');
                    $cnpass = _post('cnpass');
                    if (!Validator::Length($npass, 15, 5)) {
                        r2(getUrl('settings/change-password'), 'e', 'New Password must be 6 to 14 character');
                    }
                    if ($npass != $cnpass) {
                        r2(getUrl('settings/change-password'), 'e', 'Both Password should be same');
                    }

                    $npass = Password::_crypt($npass);
                    $d->password = $npass;
                    $d->save();

                    _msglog('s', Lang::T('Password changed successfully, Please login again'));
                    _log('[' . $admin['username'] . ']: Password changed successfully', $admin['user_type'], $admin['id']);

                    r2(getUrl('admin'));
                } else {
                    r2(getUrl('settings/change-password'), 'e', Lang::T('Incorrect Current Password'));
                }
            } else {
                r2(getUrl('settings/change-password'), 'e', Lang::T('Incorrect Current Password'));
            }
        } else {
            r2(getUrl('settings/change-password'), 'e', Lang::T('Incorrect Current Password'));
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

        $csrf_token = Csrf::generateAndStoreToken();
        $ui->assign('csrf_token', $csrf_token);
        $ui->assign('_default', json_decode(file_get_contents($UPLOAD_PATH . DIRECTORY_SEPARATOR . 'notifications.default.json'), true));
        $ui->display('admin/settings/notifications.tpl');
        break;
    case 'notifications-post':
        if ($_app_stage == 'Demo') {
            r2(getUrl('settings/notifications'), 'e', 'You cannot perform this action in Demo mode');
        }
        if (!in_array($admin['user_type'], ['SuperAdmin', 'Admin'])) {
            _alert(Lang::T('You do not have permission to access this page'), 'danger', "dashboard");
        }
        $csrf_token = _post('csrf_token');
        if (!Csrf::check($csrf_token)) {
            r2(getUrl('settings/notifications'), 'e', Lang::T('Invalid or Expired CSRF Token') . ".");
        }
        file_put_contents($UPLOAD_PATH . "/notifications.json", json_encode($_POST));
        r2(getUrl('settings/notifications'), 's', Lang::T('Settings Saved Successfully'));
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
            $ui->display('admin/settings/dbstatus.tpl');
        }
        break;

    case 'dbbackup':
        if ($_app_stage == 'Demo') {
            r2(getUrl('settings/dbstatus'), 'e', 'You cannot perform this action in Demo mode');
        }
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
        if ($_app_stage == 'Demo') {
            r2(getUrl('settings/dbstatus'), 'e', 'You cannot perform this action in Demo mode');
        }
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
            if (file_exists($_FILES['json']['tmp_name']))
                unlink($_FILES['json']['tmp_name']);
            r2(getUrl('settings/dbstatus'), 's', "Restored $suc success $fal failed");
        } else {
            r2(getUrl('settings/dbstatus'), 'e', 'Upload failed');
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
        $csrf_token = Csrf::generateAndStoreToken();
        $ui->assign('csrf_token', $csrf_token);
        $ui->display('admin/settings/language-add.tpl');
        break;

    case 'lang-post':
        if ($_app_stage == 'Demo') {
            r2(getUrl('settings/dbstatus'), 'e', 'You cannot perform this action in Demo mode');
        }
        $csrf_token = _post('csrf_token');
        if (!Csrf::check($csrf_token)) {
            r2(getUrl('settings/language'), 'e', Lang::T('Invalid or Expired CSRF Token') . ".");
        }
        file_put_contents($lan_file, json_encode($_POST, JSON_PRETTY_PRINT));
        r2(getUrl('settings/language'), 's', Lang::T('Translation saved Successfully'));
        break;

    case 'maintenance':
        if (!in_array($admin['user_type'], ['SuperAdmin', 'Admin'])) {
            _alert(Lang::T('You do not have permission to access this page'), 'danger', "dashboard");
            exit;
        }

        if (_post('save') == 'save') {
            if ($_app_stage == 'Demo') {
                r2(getUrl('settings/maintenance'), 'e', 'You cannot perform this action in Demo mode');
            }
            $csrf_token = _post('csrf_token');
            if (!Csrf::check($csrf_token)) {
                r2(getUrl('settings/maintenance'), 'e', Lang::T('Invalid or Expired CSRF Token') . ".");
            }
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

            r2(getUrl('settings/maintenance'), 's', Lang::T('Settings Saved Successfully'));
        }
        $csrf_token = Csrf::generateAndStoreToken();
        $ui->assign('csrf_token', $csrf_token);
        $ui->assign('_c', $config);
        $ui->assign('_title', Lang::T('Maintenance Mode Settings'));
        $ui->display('admin/settings/maintenance-mode.tpl');
        break;

    case 'miscellaneous':
        if (!in_array($admin['user_type'], ['SuperAdmin', 'Admin'])) {
            _alert(Lang::T('You do not have permission to access this page'), 'danger', "dashboard");
            exit;
        }
        if (_post('save') == 'save') {
            if ($_app_stage == 'Demo') {
                r2(getUrl('settings/miscellaneous'), 'e', 'You cannot perform this action in Demo mode');
            }
            $csrf_token = _post('csrf_token');
            if (!Csrf::check($csrf_token)) {
                r2(getUrl('settings/miscellaneous'), 'e', Lang::T('Invalid or Expired CSRF Token') . ".");
            }
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

            r2(getUrl('settings/miscellaneous'), 's', Lang::T('Settings Saved Successfully'));
        }
        $csrf_token = Csrf::generateAndStoreToken();
        $ui->assign('csrf_token', $csrf_token);
        $ui->assign('_c', $config);
        $ui->assign('_title', Lang::T('Miscellaneous Settings'));
        $ui->display('admin/settings/miscellaneous.tpl');
        break;

    default:
        $ui->display('admin/404.tpl');
}
