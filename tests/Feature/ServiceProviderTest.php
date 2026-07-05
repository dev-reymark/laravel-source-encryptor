<?php

namespace DevReymark\SourceEncryptor\Tests\Feature;

use DevReymark\SourceEncryptor\Tests\TestCase;
use DevReymark\SourceEncryptor\Console\BuildDistCommand;

class ServiceProviderTest extends TestCase
{
    public function test_it_registers_the_config()
    {
        // Assert the default config values are loaded
        $this->assertNotNull(config('source-encryptor'));
    }

    public function test_it_registers_the_commands_when_running_in_console()
    {
        // Orchestra Testbench runs in console mode by default.
        // We can assert the command is available via artisan.
        $commands = $this->app['console']->all();

        $this->assertArrayHasKey('source:build', $commands);
    }
}
