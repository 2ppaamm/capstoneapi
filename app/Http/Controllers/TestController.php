<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

use App\Http\Requests;
use App\Http\Controllers\Controller;
use App\Test;

class TestController extends Controller
{
    public function __construct(){
//        $this->middleware('oauth', ['except'=>['index']]);
    }

    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index()
    {
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
    public function show($id)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function update(Request $request, $id)
    {
        $test = Test::find($id);
        if(!$test) {
            return response()->json(['message'=>'This test does not exist', 'code'=>404]);
        }

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