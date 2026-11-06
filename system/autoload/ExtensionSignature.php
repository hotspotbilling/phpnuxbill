<?php

/**
 *  PHP Mikrotik Billing (https://github.com/hotspotbilling/phpnuxbill/)
 *  by https://t.me/ibnux
 **/

/**
 * Signed releases for Marketplace packages downloaded from a `zip` address.
 *
 * The developer keeps an Ed25519 private key offline and lists the public key and one signature per release in the repository
 * entry. The signature covers the extension type, its id, the version and the SHA-256 of the exact zip, so a signature cannot
 * be reused for another extension, an older version, or a different file.
 *
 * The signed text is:   phpnuxbill-extension:<type>:<id>:<version> LF <sha256 of the zip, lower-case hex>
 * Keys and signatures are hexadecimal: public key 64 characters, signature 128.
 * `php skeletons/build.php keygen|pubkey|sign` makes them (PLUGINS.md > Signing your releases).
 */
class ExtensionSignature
{
    public static function available(): bool
    {
        return function_exists('sodium_crypto_sign_verify_detached');
    }

    public static function validKey($v): bool
    {
        return is_string($v) && (bool) preg_match('/^[0-9a-f]{64}$/', $v);
    }

    public static function validSignature($v): bool
    {
        return is_string($v) && (bool) preg_match('/^[0-9a-f]{128}$/', $v);
    }

    public static function validVersion($v): bool
    {
        return is_string($v) && (bool) preg_match('/^[0-9A-Za-z][0-9A-Za-z.+_-]{0,39}$/', $v);
    }

    /** Does a repository entry carry a complete, well-formed set of signing fields on an https zip address? (MarketplaceRepo already blanks a bad zip when it loads an entry; this does not rely on that.) The one test the installer and the drawer share. */
    public static function wellFormed(array $item): bool
    {
        return Extensions::httpsUrl($item['zip'] ?? '') !== ''
            && self::validKey($item['public_key'] ?? '')
            && self::validSignature($item['signature'] ?? '')
            && self::validVersion($item['version'] ?? '');
    }

    public static function message(string $type, string $id, string $version, string $sha256): string
    {
        return 'phpnuxbill-extension:' . $type . ':' . $id . ':' . $version . "\n" . strtolower($sha256);
    }

    public static function verify(string $publicKeyHex, string $signatureHex, string $type, string $id, string $version, string $sha256): bool
    {
        if (!self::available() || !self::validKey($publicKeyHex) || !self::validSignature($signatureHex) || !self::validVersion($version)) {
            return false;
        }
        try {
            return sodium_crypto_sign_verify_detached(hex2bin($signatureHex), self::message($type, $id, $version, $sha256), hex2bin($publicKeyHex));
        } catch (Throwable $e) {
            return false;
        }
    }
}
