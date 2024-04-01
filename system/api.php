<?php

/**
 *  PHP Mikrotik Billing (https://github.com/hotspotbilling/phpnuxbill/)
 *  by https://t.me/ibnux
 *
 * This File is for API Access
 **/


if ($_SERVER['REQUEST_METHOD'] === "OPTIONS" || $_SERVER['REQUEST_METHOD'] === "HEAD") {
    header('Access-Control-Allow-Origin: *');
    header("Access-Control-Allow-Headers: Origin, X-Requested-With, Content-Type, Accept, Access-Control-Request-Method, Access-Control-Request-Headers, Authorization");
    header("HTTP/1.1 200 OK");
    die();
}

$isApi = true;

include "../init.php";

// Dummy Class
$ui = new class($key)
{
    var $assign = [];
    function display($key)
    {
        global $req;
        showResult(true, $req, $this->getAll());
    }
    function assign($key, $value)
    {
        $this->assign[$key] = $value;
    }
    function get($key)
    {
        if (isset($this->assign[$key])) {
            return $this->assign[$key];
        }
        return '';
    }
    function getTemplateVars($key)
    {
        if (isset($this->assign[$key])) {
            return $this->assign[$key];
        }
        return '';
    }
    function getAll()
    {
        return $this->assign;
    }
};

$req = _get('r');
# a/c.id.time.md5
# md5(a/c.id.time.$api_secret)
$token = _req('token');
$routes = explode('/', $req);
$handler = $routes[0];

if (!empty($token)) {
    if ($token == $config['api_key']) {
        $admin = ORM::for_table('tbl_users')->where('user_type', 'SuperAdmin')->find_one($id);
        if (empty($admin)) {
            $admin = ORM::for_table('tbl_users')->where('user_type', 'Admin')->find_one($id);
            if (empty($admin)) {
                showResult(false, Lang::T("Token is invalid"));
            }
        }
    } else {
        # validate token
        list($tipe, $uid, $time, $sha1) = explode('.', $token);
        if (trim($sha1) != sha1($uid . '.' . $time . '.' . $api_secret)) {
            showResult(false, Lang::T("Token is invalid"));
        }

        #cek token expiration
        // 3 bulan
        if ($time != 0 && time() - $time > 7776000) {
            die("$time != " . (time() - $time));
            showResult(false, Lang::T("Token Expired"), [], ['login' => true]);
        }

        if ($tipe == 'a') {
            $_SESSION['aid'] = $uid;
            $admin = Admin::_info();
        } else if ($tipe == 'c') {
            $_SESSION['uid'] = $uid;
        } else {
            showResult(false, Lang::T("Unknown Token"), [], ['login' => true]);
        }
    }

    if (!isset($handler) || empty($handler)) {
        showResult(true, Lang::T("Token is valid"));
    }


    if ($handler == 'isValid') {
        showResult(true, Lang::T("Token is valid"));
    }

    if ($handler == 'me') {
        $admin = Admin::_info();
        if (!empty($admin['id'])) {
            showResult(true, "", $admin);
        } else {
            showResult(false, Lang::T("Token is invalid"));
        }
    }
}else{
    unset($_COOKIE);
    unset($_SESSION);
}

try {
    $sys_render = File::pathFixer($root_path . 'system/controllers/' . $handler . '.php');
    if (file_exists($sys_render)) {
        include($sys_render);
        showResult(true, $req, $ui->getAll());
    } else {
        showResult(false, Lang::T('Command not found'));
    }
} catch (Exception $e) {
    showResult(false, $e->getMessage());
}
