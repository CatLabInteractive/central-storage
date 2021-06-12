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
        if (
            Config::get('airbrake.projectId') &&
            Config::get('airbrake.projectKey')
        ) {
            $this->app->bind(\Airbrake\Notifier::class, function () {
                // Create new Notifier instance.
                $notifier = new \Airbrake\Notifier([
                    'projectId' => Config::get('airbrake.projectId'),
                    'projectKey' => Config::get('airbrake.projectKey'),
                    'host' => Config::get('airbrake.host'),
                ]);

                return $notifier;
            });

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
