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
use Illuminate\Support\Facades\Mail;
use App\Mail\SendOtpMail;

Route::get('/test-mail', function () {
    Mail::to('youremail@gmail.com')->send(new SendOtpMail('123456'));
    return 'Mail sent!';
});