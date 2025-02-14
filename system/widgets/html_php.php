<?php

class html_php
{

    public function getWidget($data = null)
    {
        global $ui;
        $ui->assign('card_header', $data['title']);
        ob_start();
        try{
        eval('?>'. $data['content']);
        }catch(Exception $e){
            echo $e->getMessage();
            echo "<br>";
            echo $e->getTraceAsString();
        }
        $content = ob_get_clean();
        $ui->assign('card_body', $content);
        return $ui->fetch('widget/card_html.tpl');
    }
}