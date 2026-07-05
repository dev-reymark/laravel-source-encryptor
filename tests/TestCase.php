<?php

namespace DevReymark\SourceEncryptor\Tests;

use Orchestra\Testbench\TestCase as Orchestra;
use DevReymark\SourceEncryptor\SourceEncryptorServiceProvider;

class TestCase extends Orchestra
{
    protected function getPackageProviders($app)
    {
        return [
            SourceEncryptorServiceProvider::class,
        ];
    }
}
