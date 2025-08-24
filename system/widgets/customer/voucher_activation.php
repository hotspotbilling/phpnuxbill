<?php


class voucher_activation
{
    public function getWidget()
    {
        global $ui;
        return $ui->fetch('widget/customers/voucher_activation.tpl');
    }
}
