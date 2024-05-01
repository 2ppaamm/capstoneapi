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
    public function index()
    {
        $user = Auth::user();        
        return $user->is_admin ? response()->json(User::with('enrolledClasses.roles', 'logs')->get()): response()->json(['message' =>'No admin, not authorized to view users', 'code'=>401], 401);
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
     * Update the specified resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function update(Request $request, User $user)
    {
        $logon_user = Auth::user();

        try {
            if ($logon_user->id != $user->id && !$logon_user->is_admin) {
                return response()->json(['message' => 'You have no access rights to update user.', 'code' => 401], 401);
            }
            if ($request->email || $request->maxile_level || $request->game_level) {
                if (!$logon_user->is_admin) {
                    array_except($request, ['email', 'maxile_level', 'game_level']);
                }
            }
            if ($request->hasFile('image')) {
                if (file_exists($user->image)) {
                    unlink($user->image);
                }
                // return response()->json(['message' => 'existing file.', 'user' =>[], 'code' => 201], 201);
                $timestamp = time();
                $user->image = URL::to('/') . '/images/profiles/' . $timestamp . '.png';

                $file = $request->image->move(public_path('images/profiles'), $timestamp . '.png');
            }
            $user->fill($request->except('image'))->save();
            $user->push();
            return response()->json(['message' => 'User successfully updated.', 'user' => $user, 'code' => 201], 201);
        } catch (\Throwable $th) {
            return response()->json(['message' => 'server has error.', 'user' => $th->getMessage(), 'code' => 400], 400);
        }
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
}
