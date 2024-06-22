<?php

namespace App\Providers;

use Illuminate\Foundation\Support\Providers\RouteServiceProvider as ServiceProvider;
use Illuminate\Support\Facades\Route;

class RouteServiceProvider extends ServiceProvider
{
    protected $namespace = 'App\Http\Controllers';

    public function map()
    {
        $this->mapWebRoutes();

        $this->mapApiRoutes();
    }

    protected function mapWebRoutes()
    {
        Route::middleware('web')->namespace($this->namespace)->group(function ($router) {
            require base_path('routes/web.php');
        });
    }

    protected function mapApiRoutes()
    {
        Route::group([
            'middleware' => ['api','auth', 'throttle:60,1','bindings'],
            'namespace' => $this->namespace,
        ], function ($router) {
            require base_path('routes/api.php');
        });
    }

}

