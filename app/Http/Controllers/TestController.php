<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

use App\Http\Requests;
use App\Http\Controllers\Controller;
use App\Test;
use Auth;


class TestController extends Controller
{
    public function __construct(){
//        $user = Auth::user();        
//        $this->middleware('oauth', ['except'=>['index']]);
    }

    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index()
    {
      $logon_user = Auth::user();
        if (!$logon_user->is_admin) {
            return response()->json(['message' => 'You have no access rights to the tests','code'=>401], 401);
        }

        $tests = Test::all();
        return response()->json(['data'=>$tests], 200);
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function store(Request $request)
    {
        //
    }

    /**
     * Display the specified resource.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function show(Test $test)
    {
        $test_owner = $test->user;
        $logon_user = Auth::user();
        if ($logon_user->id != $test_owner->id && !$logon_user->is_admin) {
            return response()->json(['message' => 'You have no access rights to view the test','code'=>401], 401);
        }
        return response()->json(['test'=>$test, 'questions'=>$test->questions, 'code'=>201], 201);
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function update(Request $request, Test $test)
    {

        $field = $request->get('field');
        $value = $request->get('value');
        $test->$field = $value;
        $test->save();

        return response()->json(['data' => $test], 200);
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function destroy($id)
    {
        $test = Test::find($id);
        if(!$test) {
        return response()->json(['message'=>'This test does not exist', 'code'=>404]);
        }

        $test->delete();
        return response()->json(['message'=>'Track has been deleted.'], 200);
    }
}