<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

use App\Http\Requests;
use App\Http\Controllers\Controller;
use App\Field;
use App\Http\Requests\CreateFieldRequest;
use Auth;

class FieldController extends Controller
{
        /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index() {
        $user = Auth::user();
        return response()-> json(['message' => 'Request executed successfully', 'fields'=>Field::all()],200);
//        return response()->json(['data'=>$users], 200);
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param  \Illuminate\Http\CreateFieldRequest  $request
     * @return \Illuminate\Http\Response
     */
public function store(CreateFieldRequest $request)
    {
        $user = Auth::user();
        if (!$user->is_admin){
            return response()->json(['message'=>'Only administrators can create a new field', 'code'=>403],403);
        }
        $values = $request->all();
        $values['user_id'] = $user->id;

        $field = Field::create($values);

        return response()->json(['message'=>'Field is now added','code'=>201, 'field' => $field], 201);
    }

     /**
     * Display the specified resource.
     *
     * @param  Field $field
     * @return \Illuminate\Http\Response
     */
    public function show(Field $field)
    {
         return response()->json(['message' =>'Successful retrieval of field.', 'field'=>$field, 'code'=>201], 201);
    }

     /**
     * Update the specified resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  Field  $field
     * @return \Illuminate\Http\Response
     */
    public function update(CreateFieldRequest $request, Field $field)
    {   
        $logon_user = Auth::user();
        if ($logon_user->id != $field->user_id && !$logon_user->is_admin) {            
            return response()->json(['message' => 'You have no access rights to update field','code'=>401], 401);     
        }

        $field->fill($request->all())->save();

        return response()->json(['message'=>'Field updated','field' => $field, 201], 201);
    }

     /**
     * Remove the specified resource from storage.
     *
     * @param  Field  $field
     * @return \Illuminate\Http\Response
     */
    public function destroy(Field $field)
    {
        $logon_user = Auth::user();
        if ($logon_user->id != $field->user_id && !$logon_user->is_admin) {            
            return response()->json(['message' => 'You have no access rights to delete field','code'=>401], 401);
        } 
        $field->delete();
        return response()->json(['message'=>'This field has been deleted','code'=>201], 201);
    }
}