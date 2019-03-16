<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Str;

class FindUnusedMethods extends Command
{
    protected $defaultPaths = [];

    protected $functionNames = [];

    protected $massiveString = '';

    protected $crudNames = [
        'edit',
        'update',
        'create',
        'store',
        'destroy',
        'index',
        'show',
    ];

    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'findunused:methods';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Find unused methods';

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
            resource_path('views'),
        ]);
    }

    /**
     * Execute the console command.
     *
     * @return mixed
     */
    public function handle()
    {
        $this->defaultPaths->each(function ($path) {
            $phpFiles = collect(File::allFiles($path))->filter(function ($filename) {
                return Str::endsWith($filename, '.php') and $this->shouldConsider($filename->getPathName());
            })->each(function ($phpFile) {
                $fileContents = file_get_contents($phpFile);
                $this->massiveString .= $fileContents;
                $functionNames = [];
                preg_match_all('/function\s+([^ ]+?)\s*\(/', $fileContents, $functionNames);
                if (count($functionNames) > 0) {
                    foreach ($functionNames[1] as $fName) {
                        if ($this->ignoreCommonStuff($fName, $phpFile->getPathName())) {
                            continue;
                        }
                        $this->functionNames[$fName][] = $phpFile->getPathName();
                    }
                }
            });
        });

        foreach ($this->functionNames as $fName => $files) {
            $matches = [];
            $realFname = $this->mangleLaravelNames($fName);
            if (preg_match("/(->|::)$realFname/", $this->massiveString, $matches) === 1) {
                unset($this->functionNames[$fName]);
                continue;
            }
        }
        dump($this->functionNames);
    }

    public function ignoreCommonStuff($funcName, $fileName)
    {
        if ($funcName == 'handle' and preg_match('/(Middleware|Listeners|Commands)/', $fileName) === 1) {
            return true;
        }
        if ($funcName == 'broadcastOn' and preg_match('/Events/', $fileName) === 1) {
            return true;
        }

        return in_array($funcName, $this->crudNames) and Str::contains($fileName, 'Controller');
    }

    public function shouldConsider($filename)
    {
        if (Str::contains($filename, 'ServiceProvider')) {
            return false;
        }
        if (Str::contains($filename, 'Policies')) {
            return false;
        }
        if (Str::contains($filename, 'Observers')) {
            return false;
        }
        return true;
    }

    protected function mangleLaravelNames($fName)
    {
        $match = '';
        if (preg_match('/^scope(.+$)/', $fName, $match) === 1) {
            return Str::camel($match[1]);
        }
        if (preg_match('/^(get|set)(.+)Attribute$/', $fName, $match) === 1) {
            return Str::snake($match[2]);
        }
        return $fName; 
    }
}
