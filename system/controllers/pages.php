<?php
/**
* PHP Mikrotik Billing (https://ibnux.github.io/phpnuxbill/)
**/
_admin();
$ui->assign('_title', 'Pages');
$ui->assign('_system_menu', 'pages');

$action = $routes['1'];
$admin = Admin::_info();
$ui->assign('_admin', $admin);

if(strpos($action,"-post")===false){
    $path = __DIR__."/../../pages/".str_replace(".","",$action).".html";
    //echo $path;
    run_hook('view_edit_pages'); #HOOK
    if(file_exists($path)){
        $html = file_get_contents($path);
        $ui->assign("htmls",str_replace(["<div","</div>"],"",$html));
        $ui->assign("writeable",is_writable($path));
        $ui->assign("pageHeader",$action);
        $ui->assign("PageFile",$action);
        $ui->display('page-edit.tpl');
    }else
        $ui->display('a404.tpl');
}else{
    $action = str_replace("-post","",$action);
    $path = __DIR__."/../../pages/".str_replace(".","",$action).".html";
    if(file_exists($path)){
        $html = _post("html");
        run_hook('save_pages'); #HOOK
        if(file_put_contents($path, str_replace(["<div","</div>"],"",$html))){
            r2(U . 'pages/'.$action, 's', $_L['Success_Save_Page']);
        }else{
            r2(U . 'pages/'.$action, 'e', $_L['Failed_Save_Page']);
        }
    }else
        $ui->display('a404.tpl');
}