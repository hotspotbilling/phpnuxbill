<?php

/**
 *  PHP Mikrotik Billing (https://github.com/hotspotbilling/phpnuxbill/)
 *  by https://t.me/ibnux
 **/

/**
 * A router's saved address ("host", "host:port", "[IPv6]" or "[IPv6]:port") as the host and port the RouterOS client connects
 * to. An IPv6 address has colons of its own, so it is saved in brackets when a port follows; the host is returned without
 * them, because the client adds the brackets itself when it builds "tcp://[host]:port".
 */
class RouterAddress
{
    /** @return array{0: string, 1: string|null} [host, port or null for the default] */
    public static function split($address)
    {
        $address = trim((string) $address);
        if (preg_match('/^\[([^\]]+)\](?::(\d+))?$/', $address, $m)) {
            return [$m[1], isset($m[2]) && $m[2] !== '' ? $m[2] : null];
        }
        if (substr_count($address, ':') > 1) {
            return [$address, null]; // a bare IPv6 address, no port
        }
        $parts = explode(':', $address);
        return [$parts[0], isset($parts[1]) && $parts[1] !== '' ? $parts[1] : null];
    }
}
