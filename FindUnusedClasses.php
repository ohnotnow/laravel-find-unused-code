<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Str;

class FindUnusedClasses extends Command
{
    protected $defaultPaths = [];

    protected $classNames = [];

    protected $controllerNames = [];

    protected $massiveString = '';

    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'findunused:classes';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Find unused classes';

    /**
     * Create a new command instance.
     *
     * @return void
     */
    public function __construct()
    {
        parent::__construct();
        $this->defaultPaths = collect([
            app_path(),
        ]);
    }

    /**
     * Execute the console command.
     *
     * @return mixed
     */
    public function handle()
    {
        $this->populateControllerNamesFromRoutes();
        $this->defaultPaths->each(function ($path) {
            $phpFiles = collect(File::allFiles($path))->filter(function ($filename) {
                return Str::endsWith($filename, '.php');
            })->each(function ($phpFile) {
                $fileContents = file_get_contents($phpFile);
                if (preg_match('/class\s+(\w+)/', $fileContents, $className) === 1) {
                    // if (str_contains($className[1], 'SampleLog')) {
                    //     dump($phpFile);
                    // }
                    $this->classNames[$className[1]] = $phpFile->getPathName();
                    $fileContents = str_replace($className[1], Str::random(16), $fileContents);
                }
                $this->massiveString .= $fileContents;
            });
        });
        foreach ($this->classNames as $className => $files) {
            $matches = [];
            if (preg_match("/$className/", $this->massiveString, $matches) === 1 or $this->isARegisteredController($className)) {
                unset($this->classNames[$className]);
            }
        }
        dump($this->classNames);
    }

    public function populateControllerNamesFromRoutes()
    {
        $routes = \Route::getRoutes();
        foreach ($routes as $route) {
            try {
                [$controller, $method] = explode('@', $route->getAction()['controller']);
                $this->controllerNames[] = class_basename($controller);
            } catch (\Exception $e) {
                \Log::info('Ignoring route : ' . $route->getAction()['controller']);
            }
        }
    }

    public function isARegisteredController($className)
    {
        return in_array($className, $this->controllerNames);
    }
}
