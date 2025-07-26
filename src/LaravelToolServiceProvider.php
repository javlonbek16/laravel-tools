<?php

namespace Src;

use Illuminate\Support\ServiceProvider;
use Src\Commands\MakeStructureCommand;
use Src\Commands\SyncControllerToRouteCommand;
use Src\Commands\SyncInterfaceToRepositoryCommand;

class LaravelToolServiceProvider extends ServiceProvider
{
    public function boot()
    {
        // Publish config
        $this->publishes([
            __DIR__.'/Config/laravel-tools.php' => config_path('laravel-tools.php'),
        ], 'config');

        // Register commands
        if ($this->app->runningInConsole()) {
            $this->commands([
                MakeStructureCommand::class,
                SyncInterfaceToRepositoryCommand::class,
                SyncControllerToRouteCommand::class,
            ]);
        }
    }

    public function register()
    {
        // Merge config
        $this->mergeConfigFrom(
            __DIR__.'/Config/laravel-tools.php', 'laravel-tools'
        );
    }
}