<?php

/**
 *  PHP Mikrotik Billing (https://github.com/hotspotbilling/phpnuxbill/)
 *  by https://t.me/ibnux
 **/

_admin();
$ui->assign('_title', 'Pages');
$ui->assign('_system_menu', 'pages');

$action = $routes['1'];
$ui->assign('_admin', $admin);

if (strpos($action, "-reset") !== false) {
    if (!in_array($admin['user_type'], ['SuperAdmin', 'Admin'])) {
        _alert(Lang::T('You do not have permission to access this page'), 'danger', "dashboard");
    }
    $action = str_replace("-reset", "", $action);
    $path = "$PAGES_PATH/" . str_replace(".", "", $action) . ".html";
    $temp = "pages_template/" . str_replace(".", "", $action) . ".html";
    if (file_exists($temp)) {
        if (!copy($temp, $path)) {
            file_put_contents($path, Http::getData('https://raw.githubusercontent.com/hotspotbilling/phpnuxbill/master/pages_template/' . $action . '.html'));
        }
    } else {
        file_put_contents($path, Http::getData('https://raw.githubusercontent.com/hotspotbilling/phpnuxbill/master/pages_template/' . $action . '.html'));
    }
    r2(getUrl('pages/') . $action);
} else if (strpos($action, "-post") === false) {
    if (!in_array($admin['user_type'], ['SuperAdmin', 'Admin'])) {
        _alert(Lang::T('You do not have permission to access this page'), 'danger', "dashboard");
    }
    $path = "$PAGES_PATH/" . str_replace(".", "", $action) . ".html";
    $ui->assign("action", $action);
    //echo $path;
    run_hook('view_edit_pages'); #HOOK
    if (!file_exists($path)) {
        $temp = "pages_template/" . str_replace(".", "", $action) . ".html";
        if (file_exists($temp)) {
            if (!copy($temp, $path)) {
                touch($path);
            }
        } else {
            touch($path);
        }
    }
    if (file_exists($path)) {
        if ($action == 'Voucher') {
            if (!file_exists("$PAGES_PATH/vouchers/")) {
                mkdir("$PAGES_PATH/vouchers/");
                if (file_exists("pages_template/vouchers/")) {
                    File::copyFolder("pages_template/vouchers/", "$PAGES_PATH/vouchers/");
                }
            }
            $ui->assign("vouchers", scandir("$PAGES_PATH/vouchers/"));
        }
        $html = file_get_contents($path);
        $ui->assign("htmls", str_replace(["<div", "</div>"], "", $html));
        $ui->assign("writeable", is_writable($path));
        $ui->assign("pageHeader", str_replace('_', ' ', $action));
        $ui->assign("PageFile", $action);
        $ui->display('admin/settings/page.tpl');
    } else
        $ui->display('admin/404.tpl');
} else {
    if (!in_array($admin['user_type'], ['SuperAdmin', 'Admin'])) {
        _alert(Lang::T('You do not have permission to access this page'), 'danger', "dashboard");
    }
    $action = str_replace("-post", "", $action);
    $path = "$PAGES_PATH/" . str_replace(".", "", $action) . ".html";
    if (file_exists($path)) {
        $html = _post("html");
        run_hook('save_pages'); #HOOK
        if (file_put_contents($path, $html)) {
            if (_post('template_save') == 'yes') {
                if (!empty(_post('template_name'))) {
                    file_put_contents("$PAGES_PATH/vouchers/" . _post('template_name') . '.html', $html);
                }
            }
            r2(getUrl('pages/') . $action, 's', Lang::T("Saving page success"));
        } else {
            r2(getUrl('pages/') . $action, 'e', Lang::T("Failed to save page, make sure i can write to folder pages, <i>chmod 664 pages/*.html<i>"));
        }
    } else
        $ui->display('admin/404.tpl');
}
