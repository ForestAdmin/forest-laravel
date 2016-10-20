<?php

namespace ForestAdmin\ForestLaravel;

use Barryvdh\Cors\ServiceProvider as CorsProvider;
use ForestAdmin\ForestLaravel\Console\SendApimapCommand;
use ForestAdmin\ForestLaravel\Http\Middleware\AuthenticateForestUser;
use Illuminate\Support\ServiceProvider;

class ForestServiceProvider extends ServiceProvider {
    public function boot() {
        $this->publishes([$this->configPath() => config_path('forest.php')]);

        // NOTICE: Register Forest routes
        $namespace = $this->app->getNamespace();

        $this->app->router->middleware('auth.forest',
          AuthenticateForestUser::class);
        $this->app->router->group([
          'namespace' => $namespace.'Http\Controllers'], function() {
            require __DIR__. '/Http/routes.php';
        });

        // NOTICE: Publish the config file
        $this->mergeConfigFrom(__DIR__.'/../config/forest.php', 'forest');
        $this->mergeConfigFrom(__DIR__.'/../config/cors.php', 'cors');

        // NOTICE: Register the send-apimap command
        if ($this->app->runningInConsole()) {
            $this->commands([SendApimapCommand::class]);
        }
    }

    public function register() {
        // NOTICE: Subscribe to the CORS provider
        $this->app->register(CorsProvider::class);
    }

    protected function configPath() {
        return __DIR__.'/../config/forestDefault.php';
    }
}
