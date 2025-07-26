<?php

namespace Src\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\File;
use ReflectionClass;
use ReflectionMethod;
use Illuminate\Support\Str;

class SyncControllerToRouteCommand extends Command
{
    protected $signature = 'sync:routes {name}
                            {--controller-path= : Override default controller path}
                            {--route-path= : Override default route path}';
    
    protected $description = 'Generate Route class from Controller methods and @Route annotations';

    public function handle()
    {
        $name = ucfirst($this->argument('name'));
        
        // Get paths from config or options
        $controllerPath = $this->getPath('controllers', 'controller-path');
        $routePath = $this->getPath('routes', 'route-path');
        
        // Get namespaces
        $controllerNamespace = config('laravel-tools.namespaces.controllers');
        $routeNamespace = config('laravel-tools.namespaces.routes');
        
        $controllerClass = "{$controllerNamespace}\\{$name}Controller";
        $routeClassName = "{$name}Route";
        $routeFilePath = app_path("{$routePath}/{$routeClassName}.php");

        if (!class_exists($controllerClass)) {
            $this->error("Controller $controllerClass not found.");
            return;
        }

        $reflection = new ReflectionClass($controllerClass);
        $methods = $reflection->getMethods(ReflectionMethod::IS_PUBLIC);

        $routeLines = [];

        foreach ($methods as $method) {
            if ($method->class === $controllerClass && $method->name !== '__construct') {
                $doc = $method->getDocComment();
                $httpMethod = 'get'; // default
                $basePath = Str::kebab($method->name);
                $extraPath = ''; 

                if ($doc) {
                    if (preg_match('/@Route\(type="(get|post|put|delete)"/i', $doc, $matches)) {
                        $httpMethod = strtolower($matches[1]);
                    }
                    if (preg_match('/path="([^"]+)"/i', $doc, $matches)) {
                        $extraPath = '/' . ltrim(trim($matches[1], '"'), '/');
                    }
                }

                $fullPath = "{$basePath}{$extraPath}";
                $routeLines[] = "        Route::{$httpMethod}('{$fullPath}', [{$name}Controller::class, '{$method->name}']);";
            }
        }

        $routeContent = "<?php

namespace {$routeNamespace};

use Illuminate\Support\Facades\Route;
use {$controllerNamespace}\\{$name}Controller;

class {$routeClassName}
{
    public static function routes()
    {
" . implode("\n", $routeLines) . "
    }
}
";

        File::ensureDirectoryExists(dirname($routeFilePath));
        File::put($routeFilePath, $routeContent);

        $this->info("✅ {$routeClassName} synced successfully from {$controllerClass}.");
    }

    private function getPath(string $configKey, string $optionKey): string
    {
        return $this->option($optionKey) ?: config("laravel-tools.paths.{$configKey}");
    }
}
