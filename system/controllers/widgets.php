<?php

/**
 *  PHP Mikrotik Billing (https://github.com/hotspotbilling/phpnuxbill/)
 *  by https://t.me/ibnux
 **/
_admin();
$ui->assign('_title', Lang::T('Widgets'));
$ui->assign('_system_menu', 'settings');

$action = alphanumeric($routes['1']);
$ui->assign('_admin', $admin);

$max = ORM::for_table('tbl_widgets')->max('position');
$max2 = substr_count($config['dashboard_cr'], '.')+substr_count($config['dashboard_cr'], ',')+1;
if($max2>$max){
    $max = $max2;
}
$ui->assign('max', $max);

if ($action == 'add') {
    $pos = alphanumeric($routes['2']);
    if ($_SERVER['REQUEST_METHOD'] == 'POST') {
        $orders = alphanumeric($_POST['orders']);
        $position = alphanumeric($_POST['position']);
        $enabled = alphanumeric($_POST['enabled']);
        $title = _post('title');
        $widget = _post('widget');
        $content = _post('content');
        $d = ORM::for_table('tbl_widgets')->create();
        $d->orders = $orders;
        $d->position = $position;
        $d->enabled = $enabled;
        $d->title = $title;
        $d->widget = $widget;
        $d->content = $content;
        $d->save();
        if ($d->id() > 0) {
            r2(getUrl('widgets'), 's', 'Widget Added Successfully');
        }
    }
    $files = scandir($WIDGET_PATH);
    $widgets = [];
    foreach ($files as $file) {
        if (strpos($file, '.php') !== false) {
            $name = ucwords(str_replace('.php', '', str_replace('_', ' ', $file)));
            $widgets[str_replace('.php', '', $file)] = $name;
        }
    }
    $widget['position'] = $pos;
    $ui->assign('do', 'add');
    $ui->assign('widgets', $widgets);
    $ui->assign('widget', $widget);
    $ui->display('admin/settings/widgets_add_edit.tpl');
} else if ($action == 'edit') {
    // if request method post then save data
    if ($_SERVER['REQUEST_METHOD'] == 'POST') {
        $id = alphanumeric($_POST['id']);
        $orders = alphanumeric($_POST['orders']);
        $position = alphanumeric($_POST['position']);
        $enabled = alphanumeric($_POST['enabled']);
        $title = _post('title');
        $widget = _post('widget');
        $content = _post('content');

        $d = ORM::for_table('tbl_widgets')->find_one($id);
        $d->orders = $orders;
        $d->position = $position;
        $d->enabled = $enabled;
        $d->title = $title;
        $d->widget = $widget;
        $d->content = $content;
        $d->save();
        r2(getUrl('widgets'), 's', 'Widget Saved Successfully');
    }
    $id = alphanumeric($routes['2']);
    $widget = ORM::for_table('tbl_widgets')->find_one($id);
    $files = scandir($WIDGET_PATH);
    $widgets = [];
    foreach ($files as $file) {
        if (strpos($file, '.php') !== false) {
            $name = ucwords(str_replace('.php', '', str_replace('_', ' ', $file)));
            $widgets[str_replace('.php', '', $file)] = $name;
        }
    }
    $ui->assign('do', 'edit');
    $ui->assign('widgets', $widgets);
    $ui->assign('widget', $widget);
    $ui->display('admin/settings/widgets_add_edit.tpl');
} else if ($action == 'delete') {
    $id = alphanumeric($routes['2']);
    $d = ORM::for_table('tbl_widgets')->find_one($id);
    if ($d) {
        $d->delete();
        r2(getUrl('widgets'), 's', 'Widget Deleted Successfully');
    }
    r2(getUrl('widgets'), 'e', 'Widget Not Found');
} else if (!empty($action) && file_exists("system/widget/$action.php") && !empty($routes['2'])) {
    require_once "system/widget/$action.php";
    try {
        (new $action)->run_command($routes['2']);
    } catch (Throwable $e) {
        //nothing to do
    }
} else if ($action == 'pos') {
    $jml = count($_POST['orders']);
    for ($i = 0; $i < $jml; $i++) {
        $d = ORM::for_table('tbl_widgets')->find_one($_POST['id'][$i]);
        $d->orders = $_POST['orders'][$i];
        $d->save();
    }
    r2(getUrl('widgets'), 's', 'Widget order Saved Successfully');
} else {
    if(_post("save") == 'struct'){
        $d = ORM::for_table('tbl_appconfig')->where('setting', 'dashboard_cr')->find_one();
        if ($d) {
            $d->value = _post('dashboard_cr');
            $d->save();
        } else {
            $d = ORM::for_table('tbl_appconfig')->create();
            $d->setting = 'dashboard_cr';
            $d->value = _post('dashboard_cr');
            $d->save();
        }
        _alert("Dashboard Structure Saved Successfully", "success", getUrl('widgets'));
    }
    $widgets = ORM::for_table('tbl_widgets')->selects("position", 1)->order_by_asc("orders")->find_many();
    $ui->assign('widgets', $widgets);
    $ui->display('admin/settings/widgets.tpl');
}
