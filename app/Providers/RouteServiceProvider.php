<?php

namespace App\Providers;

use Illuminate\Routing\Router;
use Illuminate\Foundation\Support\Providers\RouteServiceProvider as ServiceProvider;
use App\User;
use Route;

class RouteServiceProvider extends ServiceProvider
{
    /**
     * This namespace is applied to the controller routes in your routes file.
     *
     * In addition, it is set as the URL generator's root namespace.
     *
     * @var string
     */
    protected $namespace = 'App\Http\Controllers';

    /**
     * Define your route model bindings, pattern filters, etc.
     *
     * @param  \Illuminate\Routing\Router  $router
     * @return void
     */
    public function boot()
    {
        //

        parent::boot();

//        $router->bind('username', function($username){
  //          return User::whereName($username)->firstOrFail();
    //    });
    }

    /**
     * Define the routes for the application.
     *
     * @param  \Illuminate\Routing\Router  $router
     * @return void
     */
    public function map()
    {
        $this->mapApiRoutes();

        $this->mapWebRoutes();

        //
    }

    protected function mapApiRoutes()
    {
        Route::group([
            'middleware' => ['auth0.jwt', 'throttle:60,1','bindings'],
            'namespace' => $this->namespace,
        ], function ($router) {
            require base_path('routes/api.php');
        });
    }

    protected function mapWebRoutes()
    {
        Route::group([
            'namespace' => $this->namespace, //'middleware' => 'web',
        ], function ($router) {
            require base_path('routes/web.php');
        });
    }
}
