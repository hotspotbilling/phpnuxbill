<?php

class html_only
{

    public function getWidget($data = null)
    {
        global $ui;
        return $data['content'];
    }
}