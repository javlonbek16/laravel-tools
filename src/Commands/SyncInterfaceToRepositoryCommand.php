<?php

namespace Src\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\File;
use ReflectionClass;
use ReflectionMethod;
use ReflectionNamedType;
use Illuminate\Support\Str;

class SyncInterfaceToRepositoryCommand extends Command
{
    protected $signature = 'sync:interfaces {name} 
                            {--interface-path= : Override default interface path}
                            {--repository-path= : Override default repository path}';
    
    protected $description = 'Sync interface methods into corresponding repository class';

    public function handle()
    {
        $name = ucfirst($this->argument('name'));
        
        // Get paths from config or options
        $interfacePath = $this->getPath('interfaces', 'interface-path');
        $repositoryPath = $this->getPath('repositories', 'repository-path');
        
        // Get namespaces
        $interfaceNamespace = config('laravel-tools.namespaces.interfaces');
        $repositoryNamespace = config('laravel-tools.namespaces.repositories');
        
        $interfaceClass = "{$interfaceNamespace}\\{$name}Interface";
        $interfaceFile = app_path("{$interfacePath}/{$name}Interface.php");
        $repositoryFile = app_path("{$repositoryPath}/{$name}Repository.php");

        if (!class_exists($interfaceClass)) {
            if (File::exists($interfaceFile)) {
                require_once $interfaceFile;
            }
        }

        if (!interface_exists($interfaceClass)) {
            $this->error("❌ Interface not found: {$interfaceClass}");
            return;
        }

        if (!File::exists($repositoryFile)) {
            $this->error("❌ Repository file not found: {$repositoryFile}");
            return;
        }

        $ref = new ReflectionClass($interfaceClass);
        $methods = $ref->getMethods(ReflectionMethod::IS_PUBLIC);

        $repoContent = File::get($repositoryFile);
        $existingMethods = [];

        preg_match_all('/public function (\w+)\(/', $repoContent, $matches);
        if (isset($matches[1])) {
            $existingMethods = $matches[1];
        }

        // Collect necessary imports
        $useStatements = [];
        $methodStubs = '';

        foreach ($methods as $method) {
            if (in_array($method->name, $existingMethods)) {
                continue;
            }

            $params = collect($method->getParameters())->map(function ($param) use (&$useStatements) {
                $type = $param->getType();
                if ($type instanceof ReflectionNamedType && !$type->isBuiltin()) {
                    $typeName = $type->getName();
                    $useStatements[] = $typeName;
                    return class_basename($typeName) . ' $' . $param->getName();
                }
                return ($type ? $type->getName() . ' ' : '') . '$' . $param->getName();
            })->implode(', ');

            $returnType = $method->getReturnType();
            $returnStr = '';
            if ($returnType instanceof ReflectionNamedType) {
                $typeName = $returnType->getName();
                if (!$returnType->isBuiltin()) {
                    $useStatements[] = $typeName;
                    $returnStr = ': ' . class_basename($typeName);
                } else {
                    $returnStr = ': ' . $typeName;
                }
            }

            $methodStubs .= <<<EOT

    public function {$method->name}({$params}){$returnStr}
    {
        // TODO: Implement {$method->name}() method.
        return response()->json([]);
    }

EOT;
        }

        if (empty($methodStubs)) {
            $this->info("✅ All interface methods already exist in repository.");
            return;
        }

        // Add unique use statements
        $useStatements = array_unique($useStatements);
        sort($useStatements);

        foreach ($useStatements as $class) {
            if (!Str::contains($repoContent, "use {$class};")) {
                $repoContent = preg_replace('/namespace .+?;/', "$0\nuse {$class};", $repoContent, 1);
            }
        }

        // Insert methods before last }
        $repoContent = preg_replace('/}\s*$/', rtrim($methodStubs) . "\n}", $repoContent);

        File::put($repositoryFile, $repoContent);
        $this->info("✅ Synced methods from {$name}Interface to {$name}Repository.");
    }

    private function getPath(string $configKey, string $optionKey): string
    {
        return $this->option($optionKey) ?: config("laravel-tools.paths.{$configKey}");
    }
}
