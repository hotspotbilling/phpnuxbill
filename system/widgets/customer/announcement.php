<?php


class announcement
{
    public function getWidget()
    {
        global $ui;
        return $ui->fetch('widget/customers/announcement.tpl');
    }
}
