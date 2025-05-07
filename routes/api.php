<?php

use App\Http\Controllers\OTPController;
use App\Http\Controllers\VisitorController;
use App\Http\Controllers\Auth\AuthController;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Route;
use Illuminate\Http\Request;

Route::middleware('auth:sanctum')->get('/me', function (Request $request) {
    $user = $request->user();

    if (!$user) {
        return response()->json(['message' => 'Unauthenticated.'], 401);
    }

    return response()->json([
        'id' => $user->id,
        'email' => $user->email,
        'name' => $user->firstname,
    ]);
});

// === Public Routes (No Token Required) ===
Route::prefix('auth')->group(function () {
    Route::post('/request-otp', [OTPController::class, 'requestOtp']);
    Route::post('/verify-otp', [OTPController::class, 'verifyOtp']);
});

// === Protected Routes (Require Sanctum Token) ===
Route::middleware('auth:sanctum')->group(function () {

    // Dashboard & QA
    Route::get('/protected', [App\Http\Controllers\DashboardController::class, 'index']);
    Route::post('/qa', [App\Http\Controllers\CheckAnswerController::class, 'index']);
    Route::post('/qa/answer', [App\Http\Controllers\CheckAnswerController::class, 'answer']);

    // Users
    Route::apiResource('users', App\Http\Controllers\UserController::class);
    Route::get('/users/{user}/reset', [App\Http\Controllers\UserController::class, 'reset']);
    Route::get('/users/{user}/performance', [App\Http\Controllers\UserController::class, 'performance']);
    Route::post('/users/{user}/diagnostic', [App\Http\Controllers\UserController::class, 'diagnostic']);
    Route::get('/users/{user}/report', [App\Http\Controllers\DiagnosticController::class, 'report']);
  	Route::get('/users/subscription/status', [App\Http\Controllers\UserController::class, 'subscriptionStatus']);

    // Courses & Related
    Route::apiResource('courses', App\Http\Controllers\CourseController::class);
    Route::post('courses/{course}', [App\Http\Controllers\CourseController::class, 'copy']);
    Route::apiResource('courses.houses', App\Http\Controllers\CourseHouseController::class);
    Route::apiResource('courses.users', App\Http\Controllers\CourseUserController::class);
    Route::apiResource('courses.tracks', App\Http\Controllers\CourseTrackController::class);

    // Quizzes
    Route::apiResource('quizzes', App\Http\Controllers\QuizController::class);
    Route::post('/quizzes/{quiz}/copy', [App\Http\Controllers\QuizController::class, 'copy']);
    Route::get('/quizzes/create', [App\Http\Controllers\QuizController::class, 'create']);
    Route::apiResource('quizzes.houses', App\Http\Controllers\QuizHouseController::class);
    Route::apiResource('quizzes.skills', App\Http\Controllers\QuizSkillController::class);
    Route::post('/quizzes/{quiz}/generate', [App\Http\Controllers\QuizSkillController::class, 'generateQuiz']);
    Route::delete('quizzes/{quiz}/houses', [App\Http\Controllers\QuizHouseController::class, 'deleteHouses']);
    Route::delete('quizzes/{quiz}/skills', [App\Http\Controllers\QuizSkillController::class, 'deleteSkills']);

    // Other Resources
    Route::apiResources([
        'difficulties'       => App\Http\Controllers\DifficultyController::class,
        'fields'             => App\Http\Controllers\FieldController::class,
        'houses'             => App\Http\Controllers\HouseController::class,
        'levels'             => App\Http\Controllers\LevelController::class,
        'permissions'        => App\Http\Controllers\PermissionController::class,
        'roles'              => App\Http\Controllers\RoleController::class,
        'units'              => App\Http\Controllers\UnitController::class,
        'tracks'             => App\Http\Controllers\TrackController::class,
        'tests'              => App\Http\Controllers\TestController::class,
        'types'              => App\Http\Controllers\TypeController::class,
        'skills'             => App\Http\Controllers\SkillController::class,
        'questions'          => App\Http\Controllers\QuestionController::class,
        'enrolments'         => App\Http\Controllers\EnrolmentController::class,
        'users.tests'        => App\Http\Controllers\UserTestController::class,
        'houses.users'       => App\Http\Controllers\HouseUserController::class,
        'houses.tracks'      => App\Http\Controllers\HouseTrackController::class,
        'skills.questions'   => App\Http\Controllers\SkillQuestionsController::class,
        'tracks.questions'   => App\Http\Controllers\TrackQuestionsController::class,
        'tracks.skills'      => App\Http\Controllers\TrackSkillController::class,
    ]);

    // Custom Routes
    Route::post('skills/{skills}/copy', [App\Http\Controllers\SkillController::class, 'copy']);
    Route::get('skills/{skills}/passed', [App\Http\Controllers\SkillController::class, 'usersPassed']);
    Route::post('skills/search', [App\Http\Controllers\SkillController::class, 'search']);
    Route::get('skills/{skill}/tracks', [App\Http\Controllers\TrackSkillController::class, 'list_tracks']);
    Route::delete('skills/{skill}/tracks', [App\Http\Controllers\TrackSkillController::class, 'deleteTracks']);
    Route::delete('tracks/{track}/skills', [App\Http\Controllers\TrackSkillController::class, 'deleteSkills']);

    // Logs
    Route::get('users/{username}/logs', [App\Http\Controllers\LogController::class, 'show']);
    Route::get('logs', [App\Http\Controllers\LogController::class, 'index']);

    // Diagnostics & Visitors
    Route::post('/test/protected/{type}', [App\Http\Controllers\DiagnosticController::class, 'index']);
    Route::post('/test/answers', [App\Http\Controllers\DiagnosticController::class, 'answer']);
    Route::get('/test/trackquestions/{track}', [App\Http\Controllers\FieldTrackQuestionController::class, 'index']);
    Route::post('/mastercode', [VisitorController::class, 'mastercode']);
    Route::post('/diagnostic', [VisitorController::class, 'diagnostic']);
    Route::post('/subscribe', [VisitorController::class, 'subscribe']);
    Route::post('/loginInfo', [App\Http\Controllers\DiagnosticController::class, 'login']);
	Route::post('/auth/logout', [AuthController::class, 'logout']);
	Route::post('/auth/logout-all', [AuthController::class, 'logoutAll']);
});
