<?php

class info_payment_gateway
{

    public function getWidget($data = null)
    {
        global $ui;
        return $ui->fetch('widget/info_payment_gateway.tpl');
    }
}