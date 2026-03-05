<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Encryption Key
    |--------------------------------------------------------------------------
    |
    | This key is used to encrypt and decrypt your PHP source files.
    | Generate using: php -r "echo bin2hex(random_bytes(32));"
    |
    */

    'key' => env('SOURCE_ENCRYPTION_KEY'),

    /*
    |--------------------------------------------------------------------------
    | Exclude Directories
    |--------------------------------------------------------------------------
    |
    | These directories will not be encrypted.
    |
    */

    'exclude' => [
        'app/Console',
        'app/Exceptions',
        'app/Providers',
    ],

    /*
    |--------------------------------------------------------------------------
    | Output Directory
    |--------------------------------------------------------------------------
    |
    | Where encrypted files are stored.
    |
    */

    'output' => 'bootstrap/cache',

];
