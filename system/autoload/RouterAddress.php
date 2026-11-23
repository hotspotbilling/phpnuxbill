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
    /**
     * The address with the spaces around its parts taken out ("[2001:db8::1] : 8877" is "[2001:db8::1]:8877", "host : 99" is
     * "host:99"), so what is saved, checked and connected to is one spelling. A bare IPv6 address and anything else is only trimmed.
     */
    public static function normalize($address)
    {
        $address = trim((string) $address);
        if (preg_match('/^\[\s*([^\[\]]*?)\s*\]\s*(?::\s*(.*?))?$/', $address, $m)) {
            return '[' . $m[1] . ']' . (isset($m[2]) ? ':' . $m[2] : '');
        }
        if (substr_count($address, ':') === 1) {
            [$h, $p] = explode(':', $address);
            return trim($h) . ':' . trim($p);
        }
        return $address;
    }

    /** @return array{0: string, 1: string|null} [host, port or null for the default] */
    public static function split($address)
    {
        $address = self::normalize($address);
        if (preg_match('/^\[([^\]]+)\](?::(\d+))?$/', $address, $m)) {
            return [$m[1], isset($m[2]) && (int) $m[2] > 0 ? $m[2] : null];
        }
        if (substr_count($address, ':') > 1) {
            return [$address, null]; // a bare IPv6 address, no port
        }
        $parts = explode(':', $address);
        // ":0" (or no number) is no port at all: the default API port is used
        return [$parts[0], isset($parts[1]) && (int) $parts[1] > 0 ? $parts[1] : null];
    }
}
