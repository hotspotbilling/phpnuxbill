<?php


class button_order_internet_plan
{
    public function getWidget()
    {
        global $ui;
        return $ui->fetch('widget/customers/button_order_internet_plan.tpl');
    }
}
