<?php

namespace Semantechs\Chat;

use Illuminate\Support\ServiceProvider;

class ChatServiceProvider extends ServiceProvider
{
    /**
     * Bootstrap services.
     *
     * @return void
     */
    public function register()
    {
        // $this->app->make('Semantechs\Chat\Controllers\ChatController');
    }

    /**
     * Bootstrap services.
     *
     * @return void
     */
    public function boot()
    {
        $this->publishes([
            __DIR__ . '/database/migrations/' => database_path('migrations'),
        ], 'chat-migrations');
    }
}
