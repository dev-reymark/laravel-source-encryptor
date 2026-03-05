<?php

namespace DevReymark\SourceEncryptor\Console;

use Illuminate\Console\Command;
use DevReymark\SourceEncryptor\Services\EncryptService;
use Illuminate\Support\Facades\File;
use Symfony\Component\Process\Process;

class BuildDistCommand extends Command
{
    protected $signature = 'source:build
        {--no-frontend : Skip frontend build}
        {--skip-composer : Skip composer install step}';

    protected $description = 'Build production distribution with encrypted source';

    public function handle()
    {
        $start = microtime(true);

        $root = base_path();
        $dist = $root . '/dist';

        $this->info('Starting production build...');

        /*
        |--------------------------------------------------------------------------
        | Validate Encryption Key
        |--------------------------------------------------------------------------
        */

        if (!config('source-encryptor.key')) {
            $this->error('SOURCE_ENCRYPTION_KEY is not configured.');
            return self::FAILURE;
        }

        /*
        |--------------------------------------------------------------------------
        | Install Composer Dependencies
        |--------------------------------------------------------------------------
        */

        if (!$this->option('skip-composer')) {

            $this->components->task('Installing production Composer dependencies', function () use ($root) {

                $process = new Process([
                    'composer',
                    'install',
                    '--no-dev',
                    '--optimize-autoloader',
                    '--no-scripts',
                    '--no-interaction',
                    '--prefer-dist'
                ]);

                $process->setWorkingDirectory($root);
                $process->setTimeout(null);

                $process->run(function ($type, $buffer) {
                    $this->output->write($buffer);
                });

                return $process->isSuccessful();
            });
        }

        /*
        |--------------------------------------------------------------------------
        | Frontend Build Detection
        |--------------------------------------------------------------------------
        */

        if (!$this->option('no-frontend')) {

            $packageJson = base_path('package.json');

            if (File::exists($packageJson)) {

                $package = json_decode(File::get($packageJson), true);

                if (isset($package['scripts']['build'])) {

                    if (!File::exists(base_path('node_modules'))) {

                        $this->components->task('Installing npm dependencies', function () use ($root) {

                            $process = new Process(['npm', 'install']);

                            $process->setWorkingDirectory($root);
                            $process->setTimeout(null);

                            $process->run(function ($type, $buffer) {
                                $this->output->write($buffer);
                            });

                            return $process->isSuccessful();
                        });
                    }

                    $this->components->task('Building frontend assets', function () use ($root) {

                        $process = new Process(['npm', 'run', 'build']);

                        $process->setWorkingDirectory($root);
                        $process->setTimeout(null);

                        $process->run(function ($type, $buffer) {
                            $this->output->write($buffer);
                        });

                        return $process->isSuccessful();
                    });

                } else {

                    $this->line('<fg=yellow>⚠ No frontend build script detected. Skipping.</>');
                }

            } else {

                $this->line('<fg=yellow>⚠ No package.json found. Skipping frontend build.</>');
            }
        }

        /*
        |--------------------------------------------------------------------------
        | Prepare Dist Directory
        |--------------------------------------------------------------------------
        */

        $this->components->task('Preparing dist directory', function () use ($dist) {

            if (File::exists($dist)) {
                File::deleteDirectory($dist);
            }

            File::makeDirectory($dist, 0755, true);

            return true;
        });

        /*
        |--------------------------------------------------------------------------
        | Encrypt Source
        |--------------------------------------------------------------------------
        */

        $this->components->task('Encrypting source files', function () {

            $encrypt = new EncryptService();
            $encrypt->cleanOutput();
            $encrypt->encryptProject();

            return true;
        });

        /*
        |--------------------------------------------------------------------------
        | Copy Project Files
        |--------------------------------------------------------------------------
        */

        $this->info('Copying project files...');

        $dirs = [
            'bootstrap',
            'config',
            'database',
            'public',
            'resources',
            'storage',
            'vendor'
        ];

        foreach ($dirs as $dir) {

            $source = base_path($dir);
            $target = $dist . '/' . $dir;

            if (File::exists($source)) {
                File::copyDirectory($source, $target);
            }
        }

        /*
        |--------------------------------------------------------------------------
        | Copy Essential Files
        |--------------------------------------------------------------------------
        */

        File::copy(base_path('artisan'), $dist . '/artisan');

        if (File::exists(base_path('.env'))) {
            File::copy(base_path('.env'), $dist . '/.env');
        }

        File::copy(base_path('composer.json'), $dist . '/composer.json');
        File::copy(base_path('composer.lock'), $dist . '/composer.lock');

        /*
        |--------------------------------------------------------------------------
        | Create Route Loader Stubs
        |--------------------------------------------------------------------------
        */

        $this->info('Creating encrypted route loaders...');

        $routesPath = $dist . '/routes';

        if (!File::exists($routesPath)) {
            File::makeDirectory($routesPath);
        }

        foreach (File::files(base_path('routes')) as $file) {

            $name = $file->getFilenameWithoutExtension();

            $stub = <<<PHP
<?php

app(\\DevReymark\\SourceEncryptor\\Runtime\\SourceLoader::class)
    ->load('routes/{$name}.php');

PHP;

            File::put($routesPath . "/{$name}.php", $stub);
        }

        /*
        |--------------------------------------------------------------------------
        | Remove Raw Source
        |--------------------------------------------------------------------------
        */

        $this->info('Removing raw source...');
        File::deleteDirectory($dist . '/app');

        /*
        |--------------------------------------------------------------------------
        | Remove Frontend Source
        |--------------------------------------------------------------------------
        */

        $frontendDirs = [
            $dist . '/resources/js',
            $dist . '/resources/css',
            $dist . '/resources/vue',
            $dist . '/resources/react',
            $dist . '/resources/sass',
            $dist . '/resources/ts',
        ];

        foreach ($frontendDirs as $dir) {
            if (File::exists($dir)) {
                File::deleteDirectory($dir);
            }
        }

        /*
        |--------------------------------------------------------------------------
        | Patch Composer
        |--------------------------------------------------------------------------
        */

        $this->info('Patching composer.json...');

        $composerPath = $dist . '/composer.json';

        $composer = json_decode(File::get($composerPath), true);

        unset($composer['require-dev']);
        unset($composer['autoload-dev']);

        File::put(
            $composerPath,
            json_encode($composer, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES)
        );

        /*
        |--------------------------------------------------------------------------
        | Optimize Composer
        |--------------------------------------------------------------------------
        */

        $this->components->task('Optimizing Composer autoload', function () use ($dist) {

            $process = new Process([
                'composer',
                'dump-autoload',
                '--optimize',
                '--no-dev',
                '--no-scripts'
            ]);

            $process->setWorkingDirectory($dist);
            $process->setTimeout(null);
            $process->run();

            return $process->isSuccessful();
        });

        /*
        |--------------------------------------------------------------------------
        | Package Discovery
        |--------------------------------------------------------------------------
        */

        $this->components->task('Running package discovery', function () use ($dist) {

            $process = new Process(['php', 'artisan', 'package:discover']);

            $process->setWorkingDirectory($dist);
            $process->setTimeout(null);
            $process->run();

            return $process->isSuccessful();
        });

        /*
        |--------------------------------------------------------------------------
        | Build Summary
        |--------------------------------------------------------------------------
        */

        $time = round(microtime(true) - $start, 2);

        $this->newLine();
        $this->info('Distribution built successfully.');

        $this->table(
            ['Item', 'Location'],
            [
                ['Distribution Folder', $dist],
                ['Encrypted Source', 'bootstrap/cache/source.enc'],
                ['Public Entry', 'public/index.php'],
            ]
        );

        $this->line("Build completed in {$time}s");

        return self::SUCCESS;
    }
}