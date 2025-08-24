<?php


class active_internet_plan
{
    public function getWidget()
    {
        global $ui, $user;
        $_bill = User::_billing();
        $ui->assign('_bills', $_bill);
        $tcf = ORM::for_table('tbl_customers_fields')
            ->where('customer_id', $user['id'])
            ->find_many();
        $vpn = ORM::for_table('tbl_port_pool')
            ->find_one();
        $ui->assign('cf', $tcf);
        $ui->assign('vpn', $vpn);
        return $ui->fetch('widget/customers/active_internet_plan.tpl');
    }
}
