<?php


class recharge_a_friend
{
    public function getWidget()
    {
        global $ui;
        return $ui->fetch('widget/customers/recharge_a_friend.tpl');
    }
}
