<?php


class unpaid_order
{
    public function getWidget()
    {
        global $ui, $user;
        $unpaids = ORM::for_table('tbl_payment_gateway')
            ->where('username', $user['username'])
            ->where('status', 1)
            ->find_many();

        $active = [];
        foreach ($unpaids as $unpaid) {
            // expire payments past their expired_date, or older than 24 hours
            if (strtotime($unpaid['expired_date']) < time() || strtotime($unpaid['created_date'] . ' +24 hours') < time()) {
                $unpaid->status = 4;
                $unpaid->save();
                continue;
            }
            $active[] = $unpaid;
        }

        $ui->assign('unpaids', $active);
        return $ui->fetch('widget/customers/unpaid_order.tpl');
    }
}
