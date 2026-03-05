# Laravel Source Encryptor

[![Latest Version](https://img.shields.io/packagist/v/dev-reymark/laravel-source-encryptor.svg)](https://packagist.org/packages/dev-reymark/laravel-source-encryptor)
[![Total Downloads](https://img.shields.io/packagist/dt/dev-reymark/laravel-source-encryptor.svg)](https://packagist.org/packages/dev-reymark/laravel-source-encryptor)
[![License](https://img.shields.io/packagist/l/dev-reymark/laravel-source-encryptor.svg)](https://packagist.org/packages/dev-reymark/laravel-source-encryptor)
[![Laravel](https://img.shields.io/badge/Laravel-11%20%7C%2012-red.svg)](https://laravel.com)

Encrypt Laravel source code and safely distribute applications **without exposing PHP source files**. Converts your Laravel application's PHP files into encrypted code that is decrypted only at runtime.

## Features
- Encrypt controllers, models, services, and routes
- Bundle encrypted code into a single runtime file
- Runtime decryption via custom autoloader
- Cross-platform (Windows, Linux, macOS)
- Laravel 11 & 12 support
- No external PHP extensions required

## Quick Installation
```bash
composer require dev-reymark/laravel-source-encryptor
php artisan vendor:publish --tag=source-encryptor-config
```

## Configuration
Add to `.env`:
```
SOURCE_ENCRYPTION_KEY=your_hex_key_here
```
Generate a secure key: 
```bash
php -r "echo bin2hex(random_bytes(32));"
```

## Usage
### Build Production Distribution
First, install without dev dependencies:
```bash
composer install --no-dev --optimize-autoloader
```

Then build:
```bash
php artisan source:build
```

The command will:
1. Encrypt application source code
2. Bundle encrypted files into a single runtime file
3. Remove the original `app` directory
4. Generate encrypted route loaders
5. Create a clean **distribution folder** at `dist/`

## Distribution Structure
```
dist/
 ├ artisan
 ├ bootstrap/
 │   └ cache/
 │       └ source.enc
 ├ routes/
 ├ config/
 ├ public/
 ├ storage/
 └ vendor/
```

The original `app/` directory is removed. All encrypted source code is stored inside `bootstrap/cache/source.enc`.

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

## Requirements
- PHP **8.2+**
- Laravel **11 or 12**
- OpenSSL extension enabled

## Security Notes
- Keep your `SOURCE_ENCRYPTION_KEY` private
- Never commit `.env` to version control
- Only distribute the `dist/` directory

## License
MIT License

## Author
**Rey Mark Tapar**

[Website](https://reymarktapar.vercel.app) | [GitHub](https://github.com/dev-reymark/laravel-source-encryptor)