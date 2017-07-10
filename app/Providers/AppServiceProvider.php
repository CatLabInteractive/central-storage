<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use Config;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Bootstrap any application services.
     *
     * @return void
     */
    public function boot()
    {
        if (Config::get('airbrake.projectId')) {

            // Create new Notifier instance.
            $notifier = new \Airbrake\Notifier([
                'projectId' => Config::get('airbrake.projectId'),
                'projectKey' => Config::get('airbrake.projectKey'),
                'host' => Config::get('airbrake.host'),
            ]);

            // Set global notifier instance.
            \Airbrake\Instance::set($notifier);

            // Register error and exception handlers.
            $handler = new \Airbrake\ErrorHandler($notifier);
            $handler->register();

        }
    }

    /**
     * Register any application services.
     *
     * @return void
     */
    public function register()
    {
        //
    }
}
