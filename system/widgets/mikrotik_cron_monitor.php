<?php

class mikrotik_cron_monitor
{
    public function getWidget()
    {
        global $config,$ui;

        if ($config['router_check']) {
            $routeroffs = ORM::for_table('tbl_routers')->selects(['id', 'name', 'last_seen'])->where('status', 'Offline')->where('enabled', '1')->order_by_desc('name')->find_array();
            $ui->assign('routeroffs', $routeroffs);
        }

        return $ui->fetch('widget/mikrotik_cron_monitor.tpl');
    }
}