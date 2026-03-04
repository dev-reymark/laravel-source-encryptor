<?php

namespace DevReymark\SourceEncryptor\Runtime;

class SourceLoader
{
    protected array $files = [];
    protected string $key;
    protected array $loaded = [];

    public function __construct()
    {
        $this->key = hex2bin(config('source-encryptor.key'));

        $path = base_path('bootstrap/cache/source.enc');

        if (!file_exists($path)) {
            return;
        }

        $this->files = json_decode(
            file_get_contents($path),
            true
        );
    }
    
    public function load(string $relative)
    {
        if (isset($this->loaded[$relative])) {
            return;
        }

        if (!isset($this->files[$relative])) {
            return;
        }

        $data = base64_decode($this->files[$relative]);

        $iv = substr($data, 0, 16);
        $cipher = substr($data, 16);

        $decrypted = openssl_decrypt(
            $cipher,
            'AES-256-CBC',
            $this->key,
            OPENSSL_RAW_DATA,
            $iv
        );

        $code = gzuncompress($decrypted);

        eval ("?>" . $code);

        $this->loaded[$relative] = true;
    }
}