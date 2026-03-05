<?php

namespace DevReymark\SourceEncryptor\Runtime;

class ConfigLoader
{
    public static function load(string $path, string $key)
    {
        if (!file_exists($path)) {
            return;
        }

        $payload = base64_decode(file_get_contents($path));

        if ($payload === false || strlen($payload) < 17) {
            throw new \RuntimeException('Invalid encrypted config.');
        }

        $iv = substr($payload, 0, 16);
        $cipher = substr($payload, 16);

        $config = openssl_decrypt(
            $cipher,
            'AES-256-CBC',
            hex2bin($key),
            OPENSSL_RAW_DATA,
            $iv
        );

        if ($config === false) {
            throw new \RuntimeException("Config decryption failed.");
        }

        $cachePath = dirname($path) . '/config.php';

        file_put_contents($cachePath, $config);
    }
}