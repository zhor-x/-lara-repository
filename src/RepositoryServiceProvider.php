<?php

namespace ZhorX\Laravel\Repo;

use Illuminate\Filesystem\Filesystem;
use Illuminate\Support\ServiceProvider;

class RepositoryServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     *
     * @return void
     */
    public function register()
    {
        $filePath = config_path('repositories.php');

        if ((new Filesystem())->exists($filePath)) {
            $repositories = config('repositories');

            foreach($repositories as $interface => $repository) {
                $this->app->singleton(
                    $interface,
                    $repository
                );
            }
        }
    }

    /**
     * Bootstrap any application services.
     *
     * @return void
     */
    public function boot()
    {
        $this->commands([
            \ZhorX\Laravel\Repo\Commands\RepositoryServiceCommand::class,
        ]);
    }
}