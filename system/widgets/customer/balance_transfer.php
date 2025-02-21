<?php


class balance_transfer
{
    public function getWidget()
    {
        global $ui;
        return $ui->fetch('widget/customers/balance_transfer.tpl');
    }
}
