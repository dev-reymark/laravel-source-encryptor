<?php

namespace DevReymark\SourceEncryptor\Console;

use Illuminate\Console\Command;
use DevReymark\SourceEncryptor\Services\EncryptService;

class EncryptCommand extends Command
{
    protected $signature = 'source:encrypt';

    protected $description = 'Encrypt Laravel source code';

    public function handle()
    {
        $service = new EncryptService();

        $service->cleanOutput();

        $this->info("Encrypting app...");
        $service->encryptDirectory(app_path());

        $this->info("Encrypting routes...");
        $service->encryptDirectory(base_path('routes'));

        $this->info("Encryption finished.");
    }
}