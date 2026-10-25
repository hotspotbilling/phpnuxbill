<?php

/**
 *  PHP Mikrotik Billing (https://github.com/hotspotbilling/phpnuxbill/)
 *  by https://t.me/ibnux
 **/

/**
 * Device drivers: system/devices/<Class>.php (flat) or
 * system/devices/<Class>/device.json + <Class>.php (folder). The class name is the slug.
 */
class DeviceLoader extends FolderLoader
{
    protected const TYPE = 'device';

    /** Include a driver file once and return its class name (or null) */
    public static function load(string $slug): ?string
    {
        $file = static::file($slug);
        if ($file === null) {
            return null;
        }
        require_once $file;
        return class_exists($slug, false) ? $slug : null;
    }
}
