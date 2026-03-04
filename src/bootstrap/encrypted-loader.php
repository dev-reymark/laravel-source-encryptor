<?php

$base = dirname(__DIR__, 3);

$key = $_ENV['SOURCE_ENCRYPTION_KEY'] ?? null;

if (!$key) {
    return;
}

$key = hex2bin($key);

spl_autoload_register(function ($class) use ($base, $key) {

    if (!str_starts_with($class, 'App\\')) {
        return;
    }

    $relative = 'app/' . str_replace('\\', '/', substr($class, 4)) . '.php';

    $file = $base . '/storage/encrypted-source/' . $relative . '.enc';

    if (!file_exists($file)) {
        return;
    }

    $data = file_get_contents($file);

    $iv = substr($data, 0, 16);
    $encrypted = substr($data, 16);

    $decrypted = openssl_decrypt(
        $encrypted,
        'AES-256-CBC',
        $key,
        OPENSSL_RAW_DATA,
        $iv
    );

    $code = gzuncompress($decrypted);

    $tmp = tmpfile();

    fwrite($tmp, $code);

    $meta = stream_get_meta_data($tmp);

    require $meta['uri'];

}, true, true);