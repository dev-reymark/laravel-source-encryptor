<?php

namespace DevReymark\SourceEncryptor\Services;

class EncryptService
{
    protected string $key;
    protected array $exclude;
    protected string $output;

    public function __construct()
    {
        $key = config('source-encryptor.key');

        if (!$key) {
            throw new \RuntimeException("SOURCE_ENCRYPTION_KEY not configured.");
        }

        $this->key = hex2bin($key);

        $this->exclude = config('source-encryptor.exclude', []);

        $this->output = base_path(
            config('source-encryptor.output', 'bootstrap/cache')
        );
    }

    protected function isExcluded(string $path): bool
    {
        $path = realpath($path);

        foreach ($this->exclude as $exclude) {

            $full = realpath(base_path($exclude));

            if ($full && str_starts_with($path, $full)) {
                return true;
            }
        }

        return false;
    }

    protected function encrypt(string $content): string
    {
        $compressed = gzcompress($content);

        $iv = random_bytes(16);

        $encrypted = openssl_encrypt(
            $compressed,
            'AES-256-CBC',
            $this->key,
            OPENSSL_RAW_DATA,
            $iv
        );

        if ($encrypted === false) {
            throw new \RuntimeException("Encryption failed.");
        }

        return $iv . $encrypted;
    }

    public function cleanOutput(): void
    {
        if (!is_dir($this->output)) {
            return;
        }

        $iterator = new \RecursiveIteratorIterator(
            new \RecursiveDirectoryIterator($this->output, \FilesystemIterator::SKIP_DOTS),
            \RecursiveIteratorIterator::CHILD_FIRST
        );

        foreach ($iterator as $file) {
            if ($file->isDir()) {
                rmdir($file->getRealPath());
            } else {
                unlink($file->getRealPath());
            }
        }
    }

    public function encryptDirectory(string $dir): void
    {
        $files = new \RecursiveIteratorIterator(
            new \RecursiveDirectoryIterator($dir, \FilesystemIterator::SKIP_DOTS)
        );

        foreach ($files as $file) {

            if ($file->isDir()) {
                continue;
            }

            $path = $file->getRealPath();

            if ($this->isExcluded($path)) {
                continue;
            }

            if (!str_ends_with($path, '.php')) {
                continue;
            }

            $relative = str_replace(base_path() . DIRECTORY_SEPARATOR, '', $path);
            $relative = str_replace('\\', '/', $relative);

            $target = $this->output . DIRECTORY_SEPARATOR . $relative . '.enc';

            if (!is_dir(dirname($target))) {
                mkdir(dirname($target), 0755, true);
            }

            $data = file_get_contents($path);

            if ($data === false) {
                throw new \RuntimeException("Failed to read file: {$path}");
            }

            $encrypted = $this->encrypt($data);

            file_put_contents($target, $encrypted);

            echo "Encrypted: {$relative}\n";
        }
    }

    public function encryptProject(): void
    {
        $files = [];

        $directories = [
            app_path(),
            base_path('routes')
        ];

        foreach ($directories as $dir) {

            $iterator = new \RecursiveIteratorIterator(
                new \RecursiveDirectoryIterator($dir, \FilesystemIterator::SKIP_DOTS)
            );

            foreach ($iterator as $file) {

                if (!$file->isFile()) {
                    continue;
                }

                $path = $file->getRealPath();

                if (!str_ends_with($path, '.php')) {
                    continue;
                }

                if ($this->isExcluded($path)) {
                    continue;
                }

                $relative = str_replace(base_path() . DIRECTORY_SEPARATOR, '', $path);
                $relative = str_replace('\\', '/', $relative);

                $data = file_get_contents($path);

                $files[$relative] = base64_encode(
                    $this->encrypt($data)
                );
            }
        }

        $output = $this->output . '/source.enc';

        if (!is_dir($this->output)) {
            mkdir($this->output, 0755, true);
        }

        file_put_contents(
            $output,
            json_encode($files)
        );
    }
}