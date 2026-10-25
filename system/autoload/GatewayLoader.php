<?php

/**
 *  PHP Mikrotik Billing (https://github.com/hotspotbilling/phpnuxbill/)
 *  by https://t.me/ibnux
 **/

/**
 * Payment gateways: system/paymentgateway/<slug>.php (flat) or
 * system/paymentgateway/<slug>/gateway.json + <slug>.php + ui/*.tpl (folder).
 * Folder gateways can reference their templates by plain name or as "pg:name.tpl".
 */
class GatewayLoader extends FolderLoader
{
    protected const TYPE = 'gateway';

    /** Locate a gateway template for the "pg:" Smarty resource: flat ui/ first, then folder ui/ dirs */
    public static function resolveTemplate(string $name): ?string
    {
        if (!preg_match('#^[A-Za-z0-9_./-]+\.tpl$#', $name) || strpos($name, '..') !== false) {
            return null;
        }
        $dirs = array_merge([Extensions::root('gateway') . DIRECTORY_SEPARATOR . 'ui' . DIRECTORY_SEPARATOR], self::templateDirs());
        foreach ($dirs as $d) {
            if (is_file($d . $name)) {
                return $d . $name;
            }
        }
        return null;
    }
}
