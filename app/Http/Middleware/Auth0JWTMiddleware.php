<?php 

namespace App\Http\Middleware;
use Auth0\Laravel\Facade\Auth0;
use App\User;

class Auth0JWTMiddleware {
    public function handle($request, \Closure $next)
    {
        if (!auth()->check()) {
            return \Response::make("Unauthorized user. Wrong Token", 401);
        }
            
        $user = auth()->user();
        
        if (!$user) {
            return \Response::make("Unauthorized user", 401);
        }

        $currentuser = User::updateOrCreate(['email'=>$user->email],[
                'firstname' => $user->given_name,
                'lastname' => $user->family_name,     
                'email' => $user->email,
                'name' => $user->name,
                'image' => $user->picture
        ]);
        
        // lets log the user in so it is accessible
        \Auth::login($currentuser);
        // continue the execution
        return $next($request);
    }
}