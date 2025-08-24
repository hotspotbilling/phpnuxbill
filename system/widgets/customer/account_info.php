<?php


class account_info
{
    public function getWidget()
    {
        global $ui;

        $abills = User::getAttributes("Bill");
        $ui->assign('abills', $abills);
        return $ui->fetch('widget/customers/account_info.tpl');
    }
}
