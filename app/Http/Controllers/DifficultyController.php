<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

use App\Http\Requests;
use App\Http\Controllers\Controller;
use App\Difficulty;
use App\Http\Requests\CreateDifficultyRequest;
use Auth;

class DifficultyController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index() {
        $user = Auth::user();
        return response()-> json(['message' => 'Request executed successfully', 'difficulties'=>Difficulty::all()],200);
//        return response()->json(['data'=>$users], 200);
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param  \Illuminate\Http\CreateDifficultyRequest  $request
     * @return \Illuminate\Http\Response
     */
public function store(CreateDifficultyRequest $request)
    {
        $user = Auth::user();
        if (!$user->is_admin){
            return response()->json(['message'=>'Only administrators can create a new difficulty', 'code'=>403],403);
        }
        $values = $request->all();
        $values['user_id'] = $user->id;

        $difficulty = Difficulty::create($values);

        return response()->json(['message'=>'Difficulty is now added','code'=>201, 'difficulty' => $difficulty], 201);
    }

     /**
     * Display the specified resource.
     *
     * @param  Difficulty $difficulty
     * @return \Illuminate\Http\Response
     */
    public function show(Difficulty $difficulty)
    {
         return response()->json(['message' =>'Successful retrieval of difficulty.', 'difficulty'=>$difficulty, 'code'=>201], 201);
    }

     /**
     * Update the specified resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  Difficulty  $difficulty
     * @return \Illuminate\Http\Response
     */
    public function update(Request $request, Difficulty $difficulty)
    {   
        $logon_user = Auth::user();
        if ($logon_user->id != $difficulty->user_id && !$logon_user->is_admin) {            
            return response()->json(['message' => 'You have no access rights to update difficulty','code'=>401], 401);     
        }

        $difficulty->fill($request->all())->save();

        return response()->json(['message'=>'Difficulty updated','difficulty' => $difficulty, 201], 201);
    }

     /**
     * Remove the specified resource from storage.
     *
     * @param  Difficulty  $difficulty
     * @return \Illuminate\Http\Response
     */
    public function destroy(Difficulty $difficulty)
    {
        $logon_user = Auth::user();
        if ($logon_user->id != $difficulty->user_id && !$logon_user->is_admin) {            
            return response()->json(['message' => 'You have no access rights to delete difficulty','code'=>401], 401);
        } 
        $difficulty->delete();
        return response()->json(['message'=>'This difficulty has been deleted','code'=>201], 201);
    }
}
