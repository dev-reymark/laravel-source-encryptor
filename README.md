# Laravel Source Encryptor

[![Latest Version](https://img.shields.io/packagist/v/dev-reymark/laravel-source-encryptor.svg)](https://packagist.org/packages/dev-reymark/laravel-source-encryptor)
[![Total Downloads](https://img.shields.io/packagist/dt/dev-reymark/laravel-source-encryptor.svg)](https://packagist.org/packages/dev-reymark/laravel-source-encryptor)
[![License](https://img.shields.io/packagist/l/dev-reymark/laravel-source-encryptor.svg)](https://packagist.org/packages/dev-reymark/laravel-source-encryptor)
[![Laravel](https://img.shields.io/badge/Laravel-7.x_--_13.x-red.svg)](https://laravel.com)
[![Tests](https://github.com/dev-reymark/laravel-source-encryptor/actions/workflows/run-tests.yml/badge.svg)](https://github.com/dev-reymark/laravel-source-encryptor/actions/workflows/run-tests.yml)

Encrypt Laravel source code and safely distribute applications **without exposing PHP source files**. Converts your Laravel application's PHP files into encrypted code that is decrypted only at runtime, allowing you to distribute Laravel applications while protecting your intellectual property.

## Features
- Encrypt controllers, models, services, and routes
- Bundle encrypted code into a single runtime file
- Runtime decryption via custom autoloader
- Automatic Composer and npm build handling
- Cross-platform (Windows, Linux, macOS)
- Laravel 7.x through 13.x support
- Optimized distribution builds
- No external PHP extensions required

## Quick Installation
```bash
composer require dev-reymark/laravel-source-encryptor
php artisan source:install
```

The `source:install` command will automatically publish the configuration file and securely generate and inject a `SOURCE_ENCRYPTION_KEY` into your `.env` file.

## Usage
### Build Production Distribution
```bash
php artisan source:build
```

The command will:
1. Installs Composer dependencies (--no-dev)
2. Installs npm dependencies if needed
3. Builds frontend assets (Vite / React / Vue)
4. Encrypts Laravel source files
5. Bundles encrypted code into a runtime file
6. Removes the original app/ directory
7. Generates encrypted route loaders
8. Create a clean **distribution folder** at `dist/`

## Distribution Structure
```text
dist/
 ├ artisan
 ├ bootstrap/
 │   └ cache/
 │       ├ config.enc
 │       └ source.enc
 ├ composer.json
 ├ composer.lock
 ├ database/
 ├ public/
 ├ resources/
 ├ routes/
 ├ storage/
 └ vendor/
```

The original `app/` directory is removed. All encrypted source code is stored inside `bootstrap/cache/source.enc`.

## Build Options
Skip frontend build:
```bash
php artisan source:build --no-frontend
```
By default, the build command will automatically attempt to run `npm install` and `npm run build` if it detects frontend assets. Use the `--no-frontend` flag to skip this process entirely. This is highly recommended for API-only applications or if you compile your frontend assets separately.

Skip composer install:
```bash
php artisan source:build --skip-composer
```
By default, the build command automatically runs `composer install --no-dev` inside the new `dist/` directory. Use the `--skip-composer` flag to skip this step if you are running the build in a CI/CD pipeline or Docker environment where you prefer to handle Composer installation manually to utilize caching and speed up build times.

## Configuration

You can customize the encryption behavior by modifying the `config/source-encryptor.php` file.

### Excluding Directories from Encryption

If you have specific directories (or files) that you want to exclude from the encryption process (for example, if they need to be readable by third-party packages or contain specific assets), you can add them to the `exclude` array:

```php
    'exclude' => [
        // Do not remove 'bootstrap' or 'storage' — Laravel requires these to remain as physical, unencrypted files.
        'bootstrap',
        'storage',
        // Add your own custom directories or files to exclude here:
        'app/Http/Controllers/Public',
    ],
```
Any files inside these excluded paths will be copied over to the `dist` directory exactly as they are, without being encrypted.

## Running the Encrypted Application
```bash
cd dist
php artisan serve
```

Laravel automatically loads encrypted classes through the runtime loader.

## How It Works
1. PHP files are compressed and encrypted using **AES-256-CBC**
2. Encrypted code is bundled into `bootstrap/cache/source.enc`
3. During runtime: Autoload request → EncryptedAutoloader → SourceLoader decrypts → PHP executes
4. Decrypted source **never persists on disk**

## Frontend Support
The build system automatically detects frontend environments:

| Environment       | Behavior                             |
| ----------------- | ------------------------------------ |
| API-only Laravel  | Skips frontend build                 |
| Laravel + Blade   | Skips if no build script             |
| Vue Starter Kit   | Runs `npm install` + `npm run build` |
| React Starter Kit | Runs `npm install` + `npm run build` |
| Vite Projects     | Fully supported                      |

## Requirements
- PHP **7.2.5+**
- Laravel **7.x - 13.x**
- OpenSSL extension enabled
- Composer

## Security Notes
- Keep your `SOURCE_ENCRYPTION_KEY` private
- Never commit `.env` to version control
- Only distribute the `dist/` directory

## License
MIT License

## Author
**Rey Mark Tapar**

[Website](https://reymarktapar.vercel.app) | [GitHub](https://github.com/dev-reymark/laravel-source-encryptor)