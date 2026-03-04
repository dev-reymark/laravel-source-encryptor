<?php

namespace DevReymark\SourceEncryptor\Runtime;

class EncryptedAutoloader
{
    public static function register()
    {
        $loader = app(SourceLoader::class);

        spl_autoload_register(function ($class) use ($loader) {

            if (!str_starts_with($class, 'App\\')) {
                return;
            }

            $relative = str_replace('\\', '/', $class) . '.php';

            $loader->load($relative);
        });
    }
}