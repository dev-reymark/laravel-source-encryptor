<?php

namespace DevReymark\SourceEncryptor\Runtime;

class Decryptor
{
    protected string $key;

    public function __construct()
    {
        $this->key = hex2bin(config('source-encryptor.key'));
    }

    public function load(string $relative)
    {
        $path = storage_path("encrypted-source/{$relative}");

        if (!file_exists($path)) {
            throw new \RuntimeException("Encrypted file not found: {$relative}");
        }

        $data = file_get_contents($path);

        $iv = substr($data, 0, 16);
        $cipher = substr($data, 16);

        $decrypted = openssl_decrypt(
            $cipher,
            'AES-256-CBC',
            $this->key,
            OPENSSL_RAW_DATA,
            $iv
        );

        if ($decrypted === false) {
            throw new \RuntimeException("Failed to decrypt: {$relative}");
        }

        $code = gzuncompress($decrypted);

        eval ("?>" . $code);
    }
}