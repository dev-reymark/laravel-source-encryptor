<?php

namespace DevReymark\SourceEncryptor\Runtime;

class SourceLoader
{
    protected $files = [];
    protected $key;
    protected $loaded = [];

    public function __construct(string $path = null)
    {
        $path = $path ?? (function_exists('base_path') ? base_path('bootstrap/cache/source.enc') : __DIR__ . '/../../../bootstrap/cache/source.enc');

        if (!file_exists($path)) {
            $this->files = [];
            return;
        }

        $key = $GLOBALS['__SOURCE_ENCRYPTION_KEY__'] ?? null;

        if (!$key) {
            throw new \RuntimeException('Encryption key not available.');
        }

        $this->key = hex2bin($key);
        $this->files = json_decode(file_get_contents($path), true) ?? [];

        static $registered = false;
        
        if (!$registered) {
            spl_autoload_register(function ($class) {
                if (strncmp($class, 'App\\', 4) !== 0) {
                    return;
                }

                $relative = 'app/' . str_replace('\\', '/', substr($class, 4)) . '.php';

                if (isset($this->files[$relative])) {
                    $this->load($relative);
                }
            }, true, true);
            
            $registered = true;
        }
    }

    public function load(string $relative)
    {
        if (empty($this->files)) {
            require base_path($relative);
            return;
        }

        if (isset($this->loaded[$relative])) {
            return;
        }

        if (!isset($this->files[$relative])) {
            $path = base_path($relative);
            if (file_exists($path)) {
                require $path;
                return;
            }
            throw new \RuntimeException("Encrypted file not found: " . $relative);
        }

        $code = $this->decrypt($relative);

        $code = $this->rewriteIncludes($code, dirname($relative));

        $this->loaded[$relative] = true;

        eval ("?>" . $code);
    }

    protected function decrypt(string $relative): string
    {
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

        return gzuncompress($decrypted);
    }

    protected function rewriteIncludes(string $code, string $base): string
    {
        return preg_replace_callback(
            '/(require|require_once|include|include_once)\s*\(?\s*(?:__DIR__\s*\.\s*)?[\'"](.+?\.php)[\'"]\s*\)?\s*;/',
            function ($matches) use ($base) {

                $file = $matches[2];

                $path = $base . '/' . $file;

                $path = str_replace(['\\', '//'], '/', $path);
                $path = preg_replace('#/\.#', '', $path);

                return 'app(\\DevReymark\\SourceEncryptor\\Runtime\\SourceLoader::class)->load("' . $path . '");';
            },
            $code
        );
    }
}