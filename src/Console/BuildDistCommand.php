<?php

namespace DevReymark\SourceEncryptor\Console;

use Illuminate\Console\Command;
use DevReymark\SourceEncryptor\Services\EncryptService;
use Illuminate\Support\Facades\File;

class BuildDistCommand extends Command
{
    protected $signature = 'source:build';

    protected $description = 'Build production distribution with encrypted source';

    public function handle()
    {
        $root = base_path();
        $dist = $root . '/dist';

        $this->info("Preparing dist directory...");

        if (File::exists($dist)) {
            File::deleteDirectory($dist);
        }

        File::makeDirectory($dist);

        /*
        |-------------------------------------------
        | Encrypt Source
        |-------------------------------------------
        */

        $this->info("Encrypting source...");

        $encrypt = new EncryptService();

        $encrypt->cleanOutput();
        $encrypt->encryptProject();

        /*
        |-------------------------------------------
        | Copy Runtime Files
        |-------------------------------------------
        */

        $this->info("Copying project files...");

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

            $source = $root . '/' . $dir;
            $target = $dist . '/' . $dir;

            if (File::exists($source)) {
                File::copyDirectory($source, $target);
            }
        }

        // artisan
        File::copy($root . '/artisan', $dist . '/artisan');

        // .env
        if (File::exists($root . '/.env')) {
            File::copy($root . '/.env', $dist . '/.env');
        }

        // composer files
        File::copy($root . '/composer.json', $dist . '/composer.json');
        File::copy($root . '/composer.lock', $dist . '/composer.lock');

        /*
        |-------------------------------------------
        | Create Route Loader Stubs
        |-------------------------------------------
        */

        $this->info("Creating encrypted route loaders...");

        $routes = ['web', 'api', 'console', 'channels'];

        $routesPath = $dist . '/routes';

        if (!File::exists($routesPath)) {
            File::makeDirectory($routesPath);
        }

        foreach ($routes as $route) {

            $stub = <<<PHP
<?php

app(\\DevReymark\\SourceEncryptor\\Runtime\\SourceLoader::class)
    ->load('routes/{$route}.php');

PHP;

            File::put($routesPath . "/{$route}.php", $stub);
        }

        /*
        |-------------------------------------------
        | Remove Raw Source
        |-------------------------------------------
        */

        $this->info("Removing raw source...");

        File::deleteDirectory($dist . '/app');

        /*
        |-------------------------------------------
        | Patch Composer Autoload
        |-------------------------------------------
        */

        $this->info("Patching composer.json...");

        $composerPath = $dist . '/composer.json';

        $composer = json_decode(File::get($composerPath), true);

        unset($composer['require-dev']);

        File::put(
            $composerPath,
            json_encode($composer, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES)
        );

        /*
|-------------------------------------------
| Remove Dev Service Providers
|-------------------------------------------
*/

        $this->info("Cleaning dev service providers...");

        $configPath = $dist . '/config/app.php';

        if (File::exists($configPath)) {

            $config = File::get($configPath);

            $providersToRemove = [
                'Laravel\\Pail\\PailServiceProvider',
            ];

            foreach ($providersToRemove as $provider) {

                $config = str_replace(
                    $provider . '::class,',
                    '',
                    $config
                );

                $config = str_replace(
                    $provider . '::class',
                    '',
                    $config
                );
            }

            File::put($configPath, $config);
        }

        /*
        |-------------------------------------------
        | Optimize Composer
        |-------------------------------------------
        */

        $this->info("Optimizing Composer autoload...");

        chdir($dist);

        exec('composer dump-autoload --optimize --no-dev --no-scripts');

        chdir($root);

        $this->info("Distribution built successfully.");
        $this->line("Location: {$dist}");
    }
}