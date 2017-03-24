<?php
/**
* PHP Mikrotik Billing (https://ibnux.github.io/phpmixbill/)


* @copyright	Copyright (C) 2014-2015 PHP Mikrotik Billing
* @license		GNU General Public License version 2 or later; see LICENSE.txt

**/
_admin();
$ui->assign('_title', $_L['Settings'].'- '. $config['CompanyName']);
$ui->assign('_system_menu', 'settings');

$action = $routes['1'];
$admin = Admin::_info();
$ui->assign('_admin', $admin);

switch ($action) {
    case 'app':
		if($admin['user_type'] != 'Admin'){
			r2(U."dashboard",'e',$_L['Do_Not_Access']);
		}
		
        $ui->display('app-settings.tpl');
        break;
		
    case 'localisation':
		if($admin['user_type'] != 'Admin'){
			r2(U."dashboard",'e',$_L['Do_Not_Access']);
		}
		$lan = ORM::for_table('tbl_language')->find_many();
		$ui->assign('lan',$lan);
		
        $timezonelist = Timezone::timezoneList();
        $ui->assign('tlist',$timezonelist);
        $ui->assign('xjq', ' $("#tzone").select2(); ');
        $ui->display('app-localisation.tpl');
        break;
		
    case 'users':
		if($admin['user_type'] != 'Admin'){
			r2(U."dashboard",'e',$_L['Do_Not_Access']);
		}
		
        $ui->assign('xfooter', '<script type="text/javascript" src="ui/lib/c/users.js"></script>');
		
		$username = _post('username');
		if ($username != ''){
			$paginator = Paginator::bootstrap('tbl_users','username','%'.$username.'%');
			$d = ORM::for_table('tbl_users')->where_like('username','%'.$username.'%')->offset($paginator['startpoint'])->limit($paginator['limit'])->order_by_asc('id')->find_many();
		}else{
			$paginator = Paginator::bootstrap('tbl_users');
			$d = ORM::for_table('tbl_users')->offset($paginator['startpoint'])->limit($paginator['limit'])->order_by_asc('id')->find_many();
		}
		
        $ui->assign('d',$d);
		$ui->assign('paginator',$paginator);
        $ui->display('users.tpl');
        break;

    case 'users-add':
		if($admin['user_type'] != 'Admin'){
			r2(U."dashboard",'e',$_L['Do_Not_Access']);
		}
		
        $ui->display('users-add.tpl');
        break;

    case 'users-edit':
		if($admin['user_type'] != 'Admin'){
			r2(U."dashboard",'e',$_L['Do_Not_Access']);
		}
		
        $id  = $routes['2'];
        $d = ORM::for_table('tbl_users')->find_one($id);
        if($d){
            $ui->assign('d',$d);
            $ui->display('users-edit.tpl');
        }else{
            r2(U . 'settings/users', 'e', $_L['Account_Not_Found']);
        }
        break;

    case 'users-delete':
		if($admin['user_type'] != 'Admin'){
			r2(U."dashboard",'e',$_L['Do_Not_Access']);
		}
		
        $id  = $routes['2'];
        if(($admin['id']) == $id){
            r2(U . 'settings/users', 'e', 'Sorry You can\'t delete yourself');
        }
        $d = ORM::for_table('tbl_users')->find_one($id);
        if($d){
            $d->delete();
            r2(U . 'settings/users', 's', $_L['User_Delete_Ok']);
        }else{
            r2(U . 'settings/users', 'e', $_L['Account_Not_Found']);
        }
        break;

    case 'users-post':
        $username = _post('username');
        $fullname = _post('fullname');
        $password = _post('password');
        $cpassword = _post('cpassword');
        $user_type = _post('user_type');
        $msg = '';
        if(Validator::Length($username,16,2) == false){
            $msg .= 'Username should be between 3 to 15 characters'. '<br>';
        }
        if(Validator::Length($fullname,26,2) == false){
            $msg .= 'Full Name should be between 3 to 25 characters'. '<br>';
        }
        if(!Validator::Length($password,15,5)){
            $msg .= 'Password should be between 6 to 15 characters'. '<br>';
        }
        if($password != $cpassword){
            $msg .= 'Passwords does not match'. '<br>';
        }

        $d = ORM::for_table('tbl_users')->where('username',$username)->find_one();
        if($d){
            $msg .= $_L['account_already_exist']. '<br>';
        }
		$date_now = date("Y-m-d H:i:s");
        if($msg == ''){
            $password = Password::_crypt($password);
            $d = ORM::for_table('tbl_users')->create();
            $d->username = $username;
			$d->fullname = $fullname;
            $d->password = $password;
            $d->user_type = $user_type;
			$d->status = 'Active';
			$d->creationdate = $date_now;
			
            $d->save();
			
			_log('['.$admin['username'].']: '.$_L['account_created_successfully'],'Admin',$admin['id']);
            r2(U . 'settings/users', 's', $_L['account_created_successfully']);
        }else{
            r2(U . 'settings/users-add', 'e', $msg);
        }
        break;

    case 'users-edit-post':
        $username = _post('username');
        $fullname = _post('fullname');
        $password = _post('password');
        $cpassword = _post('cpassword');

        $msg = '';
        if(Validator::Length($username,16,2) == false){
            $msg .= 'Username should be between 3 to 15 characters'. '<br>';
        }
        if(Validator::Length($fullname,26,2) == false){
            $msg .= 'Full Name should be between 3 to 25 characters'. '<br>';
        }
        if($password != ''){
            if(!Validator::Length($password,15,5)){
                $msg .= 'Password should be between 6 to 15 characters'. '<br>';
            }
            if($password != $cpassword){
                $msg .= 'Passwords does not match'. '<br>';
            }
        }

        $id = _post('id');
        $d = ORM::for_table('tbl_users')->find_one($id);
        if($d){
        }else{
            $msg .= $_L['Data_Not_Found']. '<br>';
        }

        if($d['username'] != $username){
            $c = ORM::for_table('tbl_users')->where('username',$username)->find_one();
            if($c){
                $msg .= $_L['account_already_exist']. '<br>';
            }
        }

        if($msg == ''){
            $d->username = $username;
            if($password != ''){
                $password = Password::_crypt($password);
                $d->password = $password;
            }

            $d->fullname = $fullname;
            if(($admin['id']) != $id){
                $user_type = _post('user_type');
                $d->user_type = $user_type;
            }

            $d->save();
			
			_log('['.$admin['username'].']: '.$_L['User_Updated_Successfully'],'Admin',$admin['id']);
            r2(U . 'settings/users', 's', 'User Updated Successfully');
        }else{
            r2(U . 'settings/users-edit/'.$id, 'e', $msg);
        }
        break;

    case 'app-post':
        $company = _post('company');
        $theme = _post('theme');
        $address = _post('address');
        if($company == '' OR $theme == '' OR $address == ''){
            r2(U.'settings/app','e',$_L['All_field_is_required']);
        }else{
            $d = ORM::for_table('tbl_appconfig')->where('setting','CompanyName')->find_one();
            $d->value = $company;
            $d->save();
			
			$d = ORM::for_table('tbl_appconfig')->where('setting','address')->find_one();
            $d->value = $address;
            $d->save();
			
			$phone = _post('phone');
			$d = ORM::for_table('tbl_appconfig')->where('setting','phone')->find_one();
            $d->value = $phone;
            $d->save();
			
            $d = ORM::for_table('tbl_appconfig')->where('setting','theme')->find_one();
            $d->value = $theme;
            $d->save();
			
			$note = _post('note');
			$d = ORM::for_table('tbl_appconfig')->where('setting','note')->find_one();
            $d->value = $note;
            $d->save();
			
			_log('['.$admin['username'].']: '.$_L['Settings_Saved_Successfully'],'Admin',$admin['id']);
			
            r2(U.'settings/app','s',$_L['Settings_Saved_Successfully']);
        }
        break;
		
    case 'localisation-post':
        $tzone = _post('tzone');
        $date_format = _post('date_format');
        $lan = _post('lan');
        if($tzone == '' OR $date_format == '' OR $lan == ''){
            r2(U.'settings/app','e',$_L['All_field_is_required']);
        }else{
            $d = ORM::for_table('tbl_appconfig')->where('setting','timezone')->find_one();
            $d->value = $tzone;
            $d->save();

            $d = ORM::for_table('tbl_appconfig')->where('setting','date_format')->find_one();
            $d->value = $date_format;
            $d->save();
			
            $dec_point = $_POST['dec_point'];
            if(strlen($dec_point) == '1'){
                $d = ORM::for_table('tbl_appconfig')->where('setting','dec_point')->find_one();
                $d->value = $dec_point;
                $d->save();
            }

            $thousands_sep = $_POST['thousands_sep'];
            if(strlen($thousands_sep) == '1'){
                $d = ORM::for_table('tbl_appconfig')->where('setting','thousands_sep')->find_one();
                $d->value = $thousands_sep;
                $d->save();
            }

            $currency_code = $_POST['currency_code'];
            $d = ORM::for_table('tbl_appconfig')->where('setting','currency_code')->find_one();
            $d->value = $currency_code;
            $d->save();
			
            $d = ORM::for_table('tbl_appconfig')->where('setting','language')->find_one();
            $d->value = $lan;
            $d->save();
			
			_log('['.$admin['username'].']: '.$_L['Settings_Saved_Successfully'],'Admin',$admin['id']);
            r2(U.'settings/localisation','s',$_L['Settings_Saved_Successfully']);
        }
        break;
		
    case 'change-password':
		if($admin['user_type'] != 'Admin' AND $admin['user_type'] != 'Sales'){
			r2(U."dashboard",'e',$_L['Do_Not_Access']);
		}
		
        $ui->display('change-password.tpl');
        break;

    case 'change-password-post':
        $password = _post('password');
        if($password != ''){
            $d = ORM::for_table('tbl_users')->where('username',$admin['username'])->find_one();
            if($d){
                $d_pass = $d['password'];
                if(Password::_verify($password,$d_pass) == true){
                    $npass = _post('npass');
                    $cnpass = _post('cnpass');
                    if(!Validator::Length($npass,15,5)){
                        r2(U.'settings/change-password','e','New Password must be 6 to 14 character');
                    }
                    if($npass != $cnpass){
                        r2(U.'settings/change-password','e','Both Password should be same');
                    }
					
                    $npass = Password::_crypt($npass);
                    $d->password = $npass;
                    $d->save();
					
                    _msglog('s',$_L['Password_Changed_Successfully']);
					_log('['.$admin['username'].']: Password changed successfully','Admin',$admin['id']);
					
                    r2(U.'admin');
                }else{
                    r2(U.'settings/change-password','e',$_L['Incorrect_Current_Password']);
                }
            }else{
                r2(U.'settings/change-password','e',$_L['Incorrect_Current_Password']);
            }
        }else{
            r2(U.'settings/change-password','e',$_L['Incorrect_Current_Password']);
        }
        break;


    case 'dbstatus':
		if($admin['user_type'] != 'Admin'){
			r2(U."dashboard",'e',$_L['Do_Not_Access']);
		}
		
        $dbc = new mysqli($db_host,$db_user ,$db_password,$db_name);
        if ($result = $dbc->query('SHOW TABLE STATUS')) {
            $size = 0;
            $decimals = 2;
            $tables = array();
            while($row = $result->fetch_array()){
                $size += $row["Data_length"] + $row["Index_length"];
                $total_size = ($row[ "Data_length" ] + $row[ "Index_length" ]) / 1024;
                $tables[$row['Name']]['size'] = number_format($total_size,'0');
                $tables[$row['Name']]['rows'] = $row[ "Rows" ];
                $tables[$row['Name']]['name'] = $row[ "Name" ];
            }
            $mbytes = number_format($size/(1024*1024),$decimals,$config['dec_point'],$config['thousands_sep']);

			$ui->assign('tables',$tables);
			$ui->assign('dbsize',$mbytes);
			$ui->display('dbstatus.tpl');
        }
        break;

    case 'dbbackup':
		if($admin['user_type'] != 'Admin'){
			r2(U."dashboard",'e',$_L['Do_Not_Access']);
		}
		
        try {
            $mysqli = new mysqli($db_host,$db_user ,$db_password,$db_name);
            if ($mysqli->connect_errno) {
                throw new Exception("Failed to connect to MySQL: " . $mysqli->connect_error);
            }

            header('Pragma: public');
            header('Expires: 0');
            header('Cache-Control: must-revalidate, post-check=0, pre-check=0');
            header('Content-Type: application/force-download');
            header('Content-Type: application/octet-stream');
            header('Content-Type: application/download');
            header('Content-Disposition: attachment;filename="backup_'.date('Y-m-d_h_i_s') . '.sql"');
            header('Content-Transfer-Encoding: binary');

            ob_start();
            $f_output = fopen("php://output", 'w');

            print("-- pjl SQL Dump\n");
            print("-- Server version:".$mysqli->server_info."\n");
            print("-- Generated: ".date('Y-m-d h:i:s')."\n");
            print('-- Current PHP version: '.phpversion()."\n");
            print('-- Host: '.$db_host."\n");
            print('-- Database:'.$db_name."\n");

            $aTables = array();
            $strSQL = 'SHOW TABLES';
            if (!$res_tables = $mysqli->query($strSQL))
                throw new Exception("MySQL Error: " . $mysqli->error . 'SQL: '.$strSQL);

            while($row = $res_tables->fetch_array()) {
                $aTables[] = $row[0];
            }
			
            $res_tables->free();

            foreach($aTables as $table)
            {
                print("-- --------------------------------------------------------\n");
                print("-- Structure for '". $table."'\n");
                print("--\n\n");

                $strSQL = 'SHOW CREATE TABLE '.$table;
                if (!$res_create = $mysqli->query($strSQL))
                    throw new Exception("MySQL Error: " . $mysqli->error . 'SQL: '.$strSQL);
                $row_create = $res_create->fetch_assoc();

                print("\n".$row_create['Create Table'].";\n");
                print("-- --------------------------------------------------------\n");
                print('-- Dump Data for `'. $table."`\n");
                print("--\n\n");
                $res_create->free();

                $strSQL = 'SELECT * FROM '.$table;
                if (!$res_select = $mysqli->query($strSQL))
                    throw new Exception("MySQL Error: " . $mysqli->error . 'SQL: '.$strSQL);

                $fields_info = $res_select->fetch_fields();

                while ($values = $res_select->fetch_assoc()) {
                    $strFields = '';
                    $strValues = '';
                    foreach ($fields_info as $field) {
                        if ($strFields != '') $strFields .= ',';
                        $strFields .= "`".$field->name."`";

                        if ($strValues != '') $strValues .= ',';
                        $strValues .= '"'.preg_replace('/[^(\x20-\x7F)\x0A]*/','',$values[$field->name].'"');
                    }
                    print("INSERT INTO ".$table." (".$strFields.") VALUES (".$strValues.");\n");
                }
                print("\n\n\n");
                $res_select->free();
            }
			_log('['.$admin['username'].']: '.$_L['Download_Database_Backup'],'Admin',$admin['id']);

        } catch (Exception $e) {
            print($e->getMessage());
        }

        fclose($f_output);
        print(ob_get_clean());
        $mysqli->close();

        break;
		
    case 'language':
		if($admin['user_type'] != 'Admin'){
			r2(U."dashboard",'e',$_L['Do_Not_Access']);
		}
		
        $ui->display('language-add.tpl');
        break;
		
    case 'lang-post':
        $name = _post('name');
        $folder = _post('folder');
		$translator = _post('translator');
		
        if ($name == '' OR $folder == ''){
			$msg .= $_L['All_field_is_required']. '<br>';
		}
		
        $d = ORM::for_table('tbl_language')->where('name',$name)->find_one();
        if($d){
            $msg .= $_L['Lang_already_exist']. '<br>';
        }
		if($msg == ''){
			$b = ORM::for_table('tbl_language')->create();
            $b->name = $name;
            $b->folder = $folder;
			$b->author = $translator;
            $b->save();
			
			r2(U . 'settings/localisation', 's', $_L['Created_Successfully']);
        }else{
            r2(U . 'settings/language', 'e', $msg);
        }
        break;
		
    default:
        echo 'action not defined';
}