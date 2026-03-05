# Laravel Source Encryptor

[![Latest Version](https://img.shields.io/packagist/v/dev-reymark/laravel-source-encryptor.svg)](https://packagist.org/packages/dev-reymark/laravel-source-encryptor)
[![Total Downloads](https://img.shields.io/packagist/dt/dev-reymark/laravel-source-encryptor.svg)](https://packagist.org/packages/dev-reymark/laravel-source-encryptor)
[![License](https://img.shields.io/packagist/l/dev-reymark/laravel-source-encryptor.svg)](https://packagist.org/packages/dev-reymark/laravel-source-encryptor)
[![Laravel](https://img.shields.io/badge/Laravel-11%20%7C%2012-red.svg)](https://laravel.com)

Encrypt Laravel source code and safely distribute applications **without exposing PHP source files**. Converts your Laravel application's PHP files into encrypted code that is decrypted only at runtime, allowing you to distribute Laravel applications while protecting your intellectual property.

## Features
- Encrypt controllers, models, services, and routes
- Bundle encrypted code into a single runtime file
- Runtime decryption via custom autoloader
- Automatic Composer and npm build handling
- Cross-platform (Windows, Linux, macOS)
- Laravel 11 & 12 support
- Optimized distribution builds
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

## Build Options
Skip frontend build: Useful for API-only applications
```bash
php artisan source:build --no-frontend
```

Skip composer install: Useful in CI/CD pipelines or Docker builds.
```bash
php artisan source:build --skip-composer
```

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
- PHP **8.2+**
- Laravel **11 or 12**
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