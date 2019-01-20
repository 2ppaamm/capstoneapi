<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

use App\Http\Requests;
use App\Http\Controllers\Controller;
use App\Level;
use App\Http\Requests\CreateLevelRequest;
use Auth;

class LevelController extends Controller
{
    public function __construct(){
        $this->middleware('cors');
    }

    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index()
    {
        $user = Auth::user();
        return response()-> json(['message' => 'Request executed successfully', 'levels'=>Level::all()],200);

        //return response()->json(['levels'=>$levels],200);
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param  \Illuminate\Http\CreateLevelRequest  $request
     * @return \Illuminate\Http\Response
     */
    public function store(CreateLevelRequest $request)
    {
        $user = Auth::user();
        if (!$user->is_admin){
            return response()->json(['message'=>'Only administrators can create a new level', 'code'=>403],403);
        }
        $values = $request->all();

        $level = Level::create($values);

        return response()->json(['message'=>'Level is now added','code'=>201, 'level' => $level], 201);
    }

    /**
     * Display the specified resource.
     *
     * @param  Level $level
     * @return \Illuminate\Http\Response
     */
    public function show(Level $level)
    {
        return response()->json(['message' =>'Successful retrieval of level.', 'level'=>$level, 'code'=>201], 201);
    }

 /**
     * Update the specified resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  Level  $level
     * @return \Illuminate\Http\Response
     */
    public function update(Request $request, Level $level)
    {   
        $logon_user = Auth::user();
        if ($logon_user->id != $level->user_id && !$logon_user->is_admin) {            
            return response()->json(['message' => 'You have no access rights to update level','code'=>401], 401);     
        }

        $level->fill($request->all())->save();

        return response()->json(['message'=>'Level updated','level' => $level, 201], 201);
    }

     /**
     * Remove the specified resource from storage.
     *
     * @param  Level  $level
     * @return \Illuminate\Http\Response
     */
    public function destroy(Level $level)
    {
        $logon_user = Auth::user();
        if ($logon_user->id != $level->user_id && !$logon_user->is_admin) {            
            return response()->json(['message' => 'You have no access rights to delete level','code'=>401], 401);
        } 
        $level->delete();
        return response()->json(['message'=>'This level has been deleted','code'=>201], 201);
    }
}
