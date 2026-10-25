<?php

/**
 *  PHP Mikrotik Billing (https://github.com/hotspotbilling/phpnuxbill/)
 *  by https://t.me/ibnux
 **/

/**
 * Smarty resource "pg:" - resolves gateway templates from system/paymentgateway/ui/ and from the
 * ui/ folder of every folder-style gateway:  $ui->display('pg:mygateway.tpl')
 */
class SmartyPgResource extends Smarty_Resource_Custom
{
    protected function fetch($name, &$source, &$mtime)
    {
        $path = GatewayLoader::resolveTemplate($name);
        if ($path === null) {
            $source = null;
            $mtime = null;
            return;
        }
        $source = file_get_contents($path);
        $mtime = filemtime($path);
    }
}
