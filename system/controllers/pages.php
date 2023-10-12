<?php
/**
 *  PHP Mikrotik Billing (https://github.com/hotspotbilling/phpnuxbill/)
 *  by https://t.me/ibnux
 **/

_admin();
$ui->assign('_title', 'Pages');
$ui->assign('_system_menu', 'pages');

$action = $routes['1'];
$admin = Admin::_info();
$ui->assign('_admin', $admin);

if(strpos($action,"-post")===false){
    $path = "pages/".str_replace(".","",$action).".html";
    //echo $path;
    run_hook('view_edit_pages'); #HOOK
    if(!file_exists($path)){
        $temp = "pages_template/".str_replace(".","",$action).".html";
        if(file_exists($temp)){
            if(!copy($temp, $path)){
                touch($path);
            }
        }else{
            touch($path);
        }
    }
    if(file_exists($path)){
        $html = file_get_contents($path);
        $ui->assign("htmls",str_replace(["<div","</div>"],"",$html));
        $ui->assign("writeable",is_writable($path));
        $ui->assign("pageHeader",str_replace('_', ' ', $action));
        $ui->assign("PageFile",$action);
        $ui->display('page-edit.tpl');
    }else
        $ui->display('a404.tpl');
}else{
    $action = str_replace("-post","",$action);
    $path = "pages/".str_replace(".","",$action).".html";
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