<?php

namespace DevReymark\SourceEncryptor\Loader;

use DevReymark\SourceEncryptor\Runtime\SourceLoader;

class EncryptedAutoloader
{
    public static function register()
    {
        if (extension_loaded('xdebug')) {
            exit("Debugging extensions are not allowed.");
        }

        spl_autoload_register(function ($class) {

            // only handle App namespace
            if (!str_starts_with($class, 'App\\')) {
                return;
            }

            // convert namespace to path
            $relative = 'app/' . str_replace('\\', '/', substr($class, 4)) . '.php';

            $loader = app(SourceLoader::class);

            $loader->load($relative);

        }, true, true);
    }
}