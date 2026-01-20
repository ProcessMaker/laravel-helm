<?php

namespace ProcessMaker\LaravelHelm;

use Illuminate\Support\ServiceProvider;

class HelmServiceProvider extends ServiceProvider
{
    /**
     * Bootstrap the application services.
     */
    public function boot()
    {
        $this->publishes([
            __DIR__.'/../config/helm.php' => config_path('helm.php'),
        ], 'config');

        Helm::setPath(config('helm.path', '/usr/bin/helm'));
    }

    /**
     * Register the application services.
     */
    public function register()
    {
        $this->mergeConfigFrom(__DIR__.'/../config/helm.php', 'helm');
    }
}

