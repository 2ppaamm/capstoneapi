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


Route::get('opencourses', 'CourseController@open');
Route::get('leaders', 'DashboardController@leaders');

//Route::get('/loadall', 'LoadController@loadall');
//Route::get('/loadquestions', 'LoadQuestions@loadall');
//Route::get('/loadsecondary', 'LoadSecondary@loadall');
