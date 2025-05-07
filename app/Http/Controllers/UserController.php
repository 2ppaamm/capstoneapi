<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

use App\Http\Requests;
use App\Http\Controllers\Controller;
use App\Http\Requests\CreateUserRequest;
use App\User;
use Auth;
use App\Http\Requests\GameScoreRequest;
use Illuminate\Support\Facades\URL;
use Illuminate\Support\Facades\Validator;
use Illuminate\Http\JsonResponse;

class UserController extends Controller
{
    public function __construct()
    {
//        \Auth::login(User::find(2));
    }

    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index(): JsonResponse
    {
        $user = Auth::guard('sanctum')->user();
        if (!$user || !$user->is_admin) {
            return response()->json([
                'message' => 'Unauthorized: Admin access required.',
                'code' => 401,
            ], 401);
        }

        $users = User::with(['enrolledClasses.roles', 'logs'])->get();

        return response()->json($users);
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'email' => 'required|unique:users|email',
            'password' => 'required|string|min:6|regex:/^(?=.*?[A-Z])(?=.*?[a-z])(?=.*?[0-9])(?=.*?[#?!@$%^&*-]).{6,}$/',

        ]);
        if ($validator->fails()) {
            return response()->json(['message' => 'Signup is failed', 'data' => $validator->errors(), 'code' => 201]);
        }
        $user = $request->all();
        try {
            $user['password'] = bcrypt($request->password);
            User::create($user);
            return response()->json(['message' => 'User correctly added', 'data' => $user, 'code' => 201]);
        } catch (\Throwable $th) {
            return response()->json(['message' => $th->getMessage(), 'data' => $user, 'code' => 200]);
        }
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function reset($id)
    {
        $logon_user = Auth::user();
        if ($logon_user->id && !$logon_user->is_admin) {
            return response()->json(['message' => 'You have no access rights to reset user','code'=>401], 401);
        }
        $user = User::findorfail($id);
        $user->myQuestions()->detach();
        $user->testedTracks()->detach();
        $user->fields()->detach();
        $user->skill_user()->detach();
        $user->tests()->detach();
        $user->quizzes()->detach();
        $user->tests()->delete();
        $user->maxile_level = 0;
        $user->diagnostic = TRUE;
        $user->save();
        return response()->json(['message' => 'Reset for '.$user->name.' is done. There is no more record of activity of student. The game_level of '.$user->game_level .' is maintained.', 'data' => $user, 'code' => 200]);
    }


    /**
     * Mark so that the next test for the user will be the diagnostic test.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function diagnostic($id)
    {
        $logon_user = Auth::user();
        if ($logon_user->id && !$logon_user->is_admin) {
            return response()->json(['message' => 'You have no access rights to set user to do diagnostic','code'=>401], 401);
        }
        $user = User::findorfail($id);
        $user->diagnostic = $user->diagnostic ? FALSE: TRUE;
        $user->save();
        return response()->json(['message' => 'Set Diagnostic for '.$user->name.' is done.', 'data' => $user, 'code' => 200]);
    }

    /**
     * Make an existing user an administrator.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function administrator($id)
    {
        $logon_user = Auth::user();
        if ($logon_user->id && !$logon_user->is_admin) {
            return response()->json(['message' => 'You have no access rights to set user to be an admin','code'=>401], 401);
        }
        $user = User::findorfail($id);
        $user->is_admin = $user->is_admin ? TRUE:FALSE;
        $user->save();
        return response()->json(['message' => 'Set Administrator for '.$user->name.' is done.', 'data' => $user, 'code' => 200]);
    }

    /**
     * Display the specified resource.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function show(User $user)
    {
        $logon_user = Auth::user();
        if ($logon_user->id != $user->id && !$logon_user->is_admin) {
            return response()->json(['message' => 'You have no access rights to view user','code'=>401], 401);
        }
        return response()->json(['user'=>$user, 'code'=>201], 201);
    }

    /**
     * 2025/04/22 - updated for Flutter 
     Update the specified resource in storage. 
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function update(Request $request, User $user){
        $authUser = Auth::guard('sanctum')->user();

        // Authorization check
        if ($authUser->id !== $user->id && !$authUser->is_admin) {
            return response()->json([
                'message' => 'You do not have permission to update this user.',
            ], 403);
        }

        // Optional: prevent unauthorized field updates
        if (!$authUser->is_admin) {
            $request->request->remove('email');
            $request->request->remove('maxile_level');
            $request->request->remove('game_level');
        }

        // Handle profile image update
        if ($request->hasFile('image')) {
            // Delete old image if it exists
            if ($user->image && file_exists(public_path(parse_url($user->image, PHP_URL_PATH)))) {
                unlink(public_path(parse_url($user->image, PHP_URL_PATH)));
            }

            $filename = time() . '.png';
            $request->file('image')->move(public_path('images/profiles'), $filename);
            $user->image = url('/images/profiles/' . $filename);
        }

        // Update user fields (excluding 'image')
        $user->fill($request->except('image'));
        $user->save();

        return response()->json([
            'message' => 'User info updated successfully.',
            'user' => $user,
        ], 200);
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  int  $id
  .   * @return \Illuminate\Http\Response
     */
    public function destroy($id)
    {
        $users = User::findorfail($id);
        $logon_user = Auth::user();
        if (!$logon_user->is_admin) {
            return response()->json(['message' => 'You have no access rights to delete user', 'data'=>$user, 'code'=>401], 500);
        }
        if (count($users->enrolledClasses)>0) {
            return response()->json(['message'=>'User has existing classes and cannot be deleted.'], 400);
        }
        $users->delete();
        return response()->json(['message'=>'User has been deleted.'], 200);
    }

    public function game_score(GameScoreRequest $request)
    {
        $user = Auth::user();
        if ($request->old_game_level != $user->game_level) {
            return response()->json(['message'=>'Old game score is incorrect. Cannot update new score', 'code'=>500], 500);
        }
        $user->game_level = $request->new_game_level;
        $user->save();
        return User::profile($user->id);
    }

    public function performance($id)
    {
        return response()->json(['message'=>'User performance retrieved',
            'performance'=>User::whereId($id)->with('tracksPassed','completedTests','fieldMaxile','tracksFailed','incompletetests')->get(),'code'=>200
    ], 200);
    }

    public function subscriptionStatus(Request $request)
    {
        $user = Auth::guard('sanctum')->user();

        if (!$user) {
            return response()->json(['error' => 'Unauthenticated'], 401);
        }
        // Check if user has any valid enrolment (non-expired class)
        $activeEnrolments = $user->enrolledClasses()->exists();
        $tracksData = App\Track::with([
            'skills' => function ($query) {
                $query->select('skills.*'); // Select only the columns from skills table
            },
            'users' => function ($query) {
                $query->where('users.id', $this->user->id)->withPivot('doneNess');
            }
        ])->whereIn('id', $tracks->pluck('id'))->get();
     
         return response()->json([
            'active' => $activeEnrolments,
            'tracks' => $trackData,
            'user' => $user,
        ]);
    }

}
