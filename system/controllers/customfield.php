<?php

/**
 *  PHP Mikrotik Billing (https://github.com/hotspotbilling/phpnuxbill/)
 *  by https://t.me/ibnux
 **/

_admin();
$ui->assign('_title', Lang::T('Custom Fields'));
$ui->assign('_system_menu', 'settings');

$action = $routes['1'];
$ui->assign('_admin', $admin);

$fieldPath = $UPLOAD_PATH . DIRECTORY_SEPARATOR . "customer_field.json";

switch ($action) {
    case 'save':
        print_r($_POST);
        $datas = [];
        $count = count($_POST['name']);
        for($n=0;$n<$count;$n++){
            if(!empty($_POST['name'][$n])){
                $datas[] = [
                    'order' => $_POST['order'][$n],
                    'name' => Text::alphanumeric(strtolower(str_replace(" ", "_", $_POST['name'][$n])), "_"),
                    'type' => $_POST['type'][$n],
                    'placeholder' => $_POST['placeholder'][$n],
                    'value' => $_POST['value'][$n],
                    'register' => $_POST['register'][$n],
                    'required' => $_POST['required'][$n]
                ];
            }
        }
        if(count($datas)>1){
            usort($datas, function ($item1, $item2) {
                return $item1['order'] <=> $item2['order'];
            });
        }
        if(file_put_contents($fieldPath, json_encode($datas))){
            r2(U . 'customfield', 's', 'Successfully saved custom fields!');
        }else{
            r2(U . 'customfield', 'e', 'Failed to save custom fields!');
        }
    default:
        $fields = [];
        if(file_exists($fieldPath)){
            $fields = json_decode(file_get_contents($fieldPath), true);
        }
        $ui->assign('fields', $fields);
        $ui->display('customfield.tpl');
        break;
}