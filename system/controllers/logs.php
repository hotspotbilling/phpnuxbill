<?php

/**
 *  PHP Mikrotik Billing (https://github.com/hotspotbilling/phpnuxbill/)
 *  by https://t.me/ibnux
 **/

_admin();
$ui->assign('_title', 'PHPNuxBill Logs');
$ui->assign('_system_menu', 'logs');

$action = $routes['1'];
$ui->assign('_admin', $admin);

if (!in_array($admin['user_type'], ['SuperAdmin', 'Admin'])) {
    _alert(Lang::T('You do not have permission to access this page'), 'danger', "dashboard");
}

// "Clean up Logs": the days to keep, from a form that carries its CSRF token. 0 when nothing was asked; a bad token or a value
// that is not a whole number of days goes back to the page. The number is bound to the query, never written into it.
$keepDays = function ($back) {
    $raw = trim((string) _post('keep'));
    if ($raw === '') {
        return 0;
    }
    if (!Csrf::check(_post('csrf_token'))) {
        r2(getUrl($back), 'e', Lang::T('Invalid or Expired CSRF Token') . '.');
    }
    if (!ctype_digit($raw) || (int) $raw < 1 || (int) $raw > 36500) {
        r2(getUrl($back), 'e', Lang::T('Enter how many days of logs to keep, as a whole number from 1'));
    }
    return (int) $raw;
};

switch ($action) {
    case 'list-csv':
        $logs = ORM::for_table('tbl_logs')
            ->select('id')
            ->select('date')
            ->select('type')
            ->select('description')
            ->select('userid')
            ->select('ip')
            ->order_by_asc('id')->find_array();
        $h = false;
        set_time_limit(-1);
        header('Pragma: public');
        header('Expires: 0');
        header('Cache-Control: must-revalidate, post-check=0, pre-check=0');
        header("Content-type: text/csv");
        header('Content-Disposition: attachment;filename="activity-logs_' . date('Y-m-d_H_i') . '.csv"');
        header('Content-Transfer-Encoding: binary');
        foreach ($logs as $log) {
            $ks = [];
            $vs = [];
            foreach ($log as $k => $v) {
                $ks[] = $k;
                $vs[] = $v;
            }
            if (!$h) {
                echo '"' . implode('";"', $ks) . "\"\n";
                $h = true;
            }
            echo '"' . implode('";"', $vs) . "\"\n";
        }
        break;
    case 'radius-csv':
        $logs = ORM::for_table('radpostauth')
            ->select('id')
            ->select('username')
            ->select('pass')
            ->select('reply')
            ->select('authdate')
            ->order_by_asc('id')->find_array();
        $h = false;
        set_time_limit(-1);
        header('Pragma: public');
        header('Expires: 0');
        header('Cache-Control: must-revalidate, post-check=0, pre-check=0');
        header("Content-type: text/csv");
        header('Content-Disposition: attachment;filename="radius-logs_' . date('Y-m-d_H_i') . '.csv"');
        header('Content-Transfer-Encoding: binary');
        foreach ($logs as $log) {
            $ks = [];
            $vs = [];
            foreach ($log as $k => $v) {
                $ks[] = $k;
                $vs[] = $v;
            }
            if (!$h) {
                echo '"' . implode('";"', $ks) . "\"\n";
                $h = true;
            }
            echo '"' . implode('";"', $vs) . "\"\n";
        }
        break;

    case 'message-csv':
        $logs = ORM::for_table('tbl_message_logs')
            ->select('id')
            ->select('message_type')
            ->select('recipient')
            ->select('message_content')
            ->select('status')
            ->select('error_message')
            ->select('sent_at')
            ->order_by_asc('id')->find_array();
        $h = false;
        set_time_limit(-1);
        header('Pragma: public');
        header('Expires: 0');
        header('Cache-Control: must-revalidate, post-check=0, pre-check=0');
        header("Content-type: text/csv");
        header('Content-Disposition: attachment;filename="message-logs_' . date('Y-m-d_H_i') . '.csv"');
        header('Content-Transfer-Encoding: binary');
        foreach ($logs as $log) {
            $ks = [];
            $vs = [];
            foreach ($log as $k => $v) {
                $ks[] = $k;
                $vs[] = $v;
            }
            if (!$h) {
                echo '"' . implode('";"', $ks) . "\"\n";
                $h = true;
            }
            echo '"' . implode('";"', $vs) . "\"\n";
        }
        break;

    case 'list':
        $q = (_post('q') ? _post('q') : _get('q'));
        $keep = $keepDays('logs/list/');
        if ($keep > 0) {
            ORM::raw_execute("DELETE FROM tbl_logs WHERE UNIX_TIMESTAMP(date) < UNIX_TIMESTAMP(DATE_SUB(NOW(), INTERVAL ? DAY))", [$keep]);
            r2(getUrl('logs/list/'), 's', "Delete logs older than $keep days");
        }
        if ($q != '') {
            $query = ORM::for_table('tbl_logs')->where_like('description', '%' . $q . '%')->order_by_desc('id');
            $d = Paginator::findMany($query, ['q' => $q]);
        } else {
            $query = ORM::for_table('tbl_logs')->order_by_desc('id');
            $d = Paginator::findMany($query);
        }

        $ui->assign('d', $d);
        $ui->assign('q', $q);
        $ui->display('admin/logs/system.tpl');
        break;
    case 'radius-rest':
        // what radius.php could not do while it answered FreeRADIUS (a file, so no RADIUS database is needed)
        $q = trim((string) (_post('q') ?: _get('q')));
        $level = _get('level') === 'error' ? 'error' : (_get('level') === 'info' ? 'info' : '');
        $ui->assign('rows', RadiusRestLog::read(500, $q, $level));
        $ui->assign('q', $q);
        $ui->assign('level', $level);
        $ui->assign('_title', Lang::T('Radius REST Logs'));
        $ui->display('admin/logs/radius-rest.tpl');
        break;
    case 'radius-rest-clear':
        if ($_SERVER['REQUEST_METHOD'] !== 'POST' || !Csrf::check(_post('csrf_token'))) {
            r2(getUrl('logs/radius-rest'), 'e', Lang::T('Invalid or Expired CSRF Token') . '.');
        }
        if (RadiusRestLog::clear()) {
            r2(getUrl('logs/radius-rest'), 's', Lang::T('Logs cleared'));
        }
        r2(getUrl('logs/radius-rest'), 'e', Lang::T('The log file could not be removed. Check that system/cache is writable.'));
        break;
    case 'radius':
        // the RADIUS database is separate: when it cannot be used, say what is missing instead of the database error
        if (($problem = SystemInfo::radiusProblem('radpostauth')) !== null) {
            $ui->assign('_title', Lang::T('RADIUS database'));
            $ui->assign('problem', $problem);
            $ui->assign('retry_url', getUrl('logs/radius/'));
            $ui->display('admin/radius/not-ready.tpl');
            break;
        }
        $q = (_post('q') ? _post('q') : _get('q'));
        $keep = $keepDays('logs/radius/');
        if ($keep > 0) {
            ORM::raw_execute("DELETE FROM radpostauth WHERE UNIX_TIMESTAMP(authdate) < UNIX_TIMESTAMP(DATE_SUB(NOW(), INTERVAL ? DAY))", [$keep], 'radius');
            r2(getUrl('logs/radius/'), 's', "Delete logs older than $keep days");
        }
        if ($q != '') {
            $query = ORM::for_table('radpostauth', 'radius')->where_like('username', '%' . $q . '%')->order_by_desc('id');
            $d = Paginator::findMany($query, ['q' => $q]);
        } else {
            $query = ORM::for_table('radpostauth', 'radius')->order_by_desc('id');
            $d = Paginator::findMany($query);
        }

        $ui->assign('d', $d);
        $ui->assign('q', $q);
        $ui->display('admin/logs/radius.tpl');
        break;

    case 'message':
        $q = _post('q') ?: _get('q');
        $keep = $keepDays('logs/message/');
        if ($keep > 0) {
            ORM::raw_execute("DELETE FROM tbl_message_logs WHERE UNIX_TIMESTAMP(sent_at) < UNIX_TIMESTAMP(DATE_SUB(NOW(), INTERVAL ? DAY))", [$keep]);
            r2(getUrl('logs/message/'), 's', "Deleted logs older than $keep days");
        }

        if ($q !== null && $q !== '') {
            $query = ORM::for_table('tbl_message_logs')
            // the search words are bound, never written into the SQL; the recipient (phone number or email) is searched too
            ->where_raw('(message_type LIKE ? OR recipient LIKE ? OR message_content LIKE ? OR status LIKE ? OR error_message LIKE ?)', array_fill(0, 5, '%' . $q . '%'))
                ->order_by_desc('sent_at');
            $d = Paginator::findMany($query, ['q' => $q]);
        } else {
            $query = ORM::for_table('tbl_message_logs')->order_by_desc('sent_at');
            $d = Paginator::findMany($query);
        }

        if ($d) {
            $ui->assign('d', $d);
        } else {
            $ui->assign('d', []);
        }

        $ui->assign('q', $q);
        $ui->display('admin/logs/message.tpl');
        break;

    default:
        r2(getUrl('logs/list/'), 's', '');
}
