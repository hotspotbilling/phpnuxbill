<?php

class cron_monitor
{
    public function getWidget()
    {
        global $UPLOAD_PATH,$ui;

        $timestampFile = "$UPLOAD_PATH/cron_last_run.txt";
        if (file_exists($timestampFile)) {
            $lastRunTime = file_get_contents($timestampFile);
            $ui->assign('run_date', date('Y-m-d h:i:s A', $lastRunTime));
        }

        return $ui->fetch('widget/cron_monitor.tpl');
    }
}