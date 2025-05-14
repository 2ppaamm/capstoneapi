<?php
/*
|--------------------------------------------------------------------------
| Web Routes
|--------------------------------------------------------------------------
|
| This file is where you may define all of the routes that are handled
| by your application. Just tell Laravel the URIs it should respond
| to using a Closure or controller method. Build something great!
|
*/


//Route::get('leaders', 'DashboardController@leaders');

//Route::get('/loadall', 'LoadController@loadall');
//Route::get('/loadquestions', 'LoadQuestions@loadall');
//Route::get('/loadsecondary', 'LoadSecondary@loadall');
/*use Illuminate\Support\Facades\Response;
use Illuminate\Support\Facades\Storage;

Route::get('/images/{folder}/{filename}', function ($folder, $filename) {
    $path = public_path("images/$folder/$filename");

    if (!file_exists($path)) {
        abort(404);
    }

    return Response::file($path, [
        'Access-Control-Allow-Origin' => '*',
        'Access-Control-Allow-Methods' => 'GET, OPTIONS',
        'Access-Control-Allow-Headers' => '*',
        'Content-Type' => mime_content_type($path),
    ]);
});
*/