<?php

namespace KajPe\UserCmd;

use Illuminate\Support\ServiceProvider;
use KajPe\UserCmd\Commands\UserCreate;
use KajPe\UserCmd\Commands\UserEdit;
use KajPe\UserCmd\Commands\UserList;
use KajPe\UserCmd\Commands\UserRemove;

class UserCmdServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        if ($this->app->runningInConsole()) {
            $this->commands([
                UserCreate::class,
                UserEdit::class,
                UserList::class,
                UserRemove::class,
            ]);
        }
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        // Publish the configuration file
        $this->publishes([
            __DIR__.'/config/cmd-user.php' => config_path('cmd-user.php'),
        ], 'config');

        // Merge default config with the published config (if any)
        $this->mergeConfigFrom(
        __DIR__.'/config/cmd-user.php', 'cmd-user'
        );

    }
}
