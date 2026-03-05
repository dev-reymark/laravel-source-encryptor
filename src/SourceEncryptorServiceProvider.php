<?php

namespace DevReymark\SourceEncryptor;

use Illuminate\Support\ServiceProvider;
use DevReymark\SourceEncryptor\Console\BuildDistCommand;
use DevReymark\SourceEncryptor\Loader\EncryptedAutoloader;

class SourceEncryptorServiceProvider extends ServiceProvider
{
    public function register()
    {
        $this->mergeConfigFrom(
            __DIR__ . '/../config/source-encryptor.php',
            'source-encryptor'
        );
    }

    public function boot()
    {
        $this->publishes([
            __DIR__ . '/../config/source-encryptor.php' => config_path('source-encryptor.php'),
        ], 'source-encryptor-config');

        EncryptedAutoloader::register();

        if ($this->app->runningInConsole()) {
            $this->commands([
                BuildDistCommand::class
            ]);
        }
    }
}