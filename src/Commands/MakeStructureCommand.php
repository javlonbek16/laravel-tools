<?php

namespace Src\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\File;

class MakeStructureCommand extends Command
{
    protected $signature = 'make:structure {name} 
                            {--controller-path= : Override default controller path}
                            {--interface-path= : Override default interface path} 
                            {--repository-path= : Override default repository path}
                            {--route-path= : Override default route path}';
    
    protected $description = 'Create Controller, Interface, Repository and bind them together, and create route file';

    public function handle()
    {
        $name = $this->argument('name');
        $studlyName = ucfirst($name);
        $controllerName = "{$studlyName}Controller";
        $interfaceName = "{$studlyName}Interface";
        $repositoryName = "{$studlyName}Repository";
        $routeName = "{$studlyName}Route";

        // Get paths from config or options
        $controllerPath = $this->getPath('controllers', 'controller-path');
        $interfacePath = $this->getPath('interfaces', 'interface-path');
        $repositoryPath = $this->getPath('repositories', 'repository-path');
        $routePath = $this->getPath('routes', 'route-path');

        // Get namespaces from config
        $controllerNamespace = config('laravel-tools.namespaces.controllers');
        $interfaceNamespace = config('laravel-tools.namespaces.interfaces');
        $repositoryNamespace = config('laravel-tools.namespaces.repositories');
        $routeNamespace = config('laravel-tools.namespaces.routes');

        // Full file paths
        $controllerFile = app_path("{$controllerPath}/{$controllerName}.php");
        $interfaceFile = app_path("{$interfacePath}/{$interfaceName}.php");
        $repositoryFile = app_path("{$repositoryPath}/{$repositoryName}.php");
        $routeFile = app_path("{$routePath}/{$routeName}.php");

        // 1. Create Interface
        File::ensureDirectoryExists(dirname($interfaceFile));
        File::put($interfaceFile, "<?php

namespace {$interfaceNamespace};

interface {$interfaceName}
{
    //
}
");

        // 2. Create Repository
        File::ensureDirectoryExists(dirname($repositoryFile));
        File::put($repositoryFile, "<?php

namespace {$repositoryNamespace};

use {$interfaceNamespace}\\{$interfaceName};

class {$repositoryName} implements {$interfaceName}
{
    //
}
");

        // 3. Create Controller
        File::ensureDirectoryExists(dirname($controllerFile));
        $controllerExtends = config('laravel-tools.templates.controller_extends', 'Controller');
        File::put($controllerFile, "<?php

namespace {$controllerNamespace};

use {$interfaceNamespace}\\{$interfaceName};

class {$controllerName} extends {$controllerExtends}
{
    public function __construct(private {$interfaceName} \${$name}Repo)
    {
        //
    }
}
");

        // 4. Create Route File
        if (config('laravel-tools.templates.generate_route_file', true)) {
            File::ensureDirectoryExists(dirname($routeFile));
            File::put($routeFile, "<?php

namespace {$routeNamespace};

use Illuminate\Support\Facades\Route;
use {$controllerNamespace}\\{$controllerName};

class {$routeName}
{
    public static function routes()
    {
        // Add your routes here
    }
}
");
        }

        $this->info("✅ Structure created successfully:");
        $this->line("   - Controller: {$controllerFile}");
        $this->line("   - Interface: {$interfaceFile}");
        $this->line("   - Repository: {$repositoryFile}");
        if (config('laravel-tools.templates.generate_route_file', true)) {
            $this->line("   - Route: {$routeFile}");
        }
    }

    private function getPath(string $configKey, string $optionKey): string
    {
        return $this->option($optionKey) ?: config("laravel-tools.paths.{$configKey}");
    }
}
