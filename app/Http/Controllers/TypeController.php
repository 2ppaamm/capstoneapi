<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

use App\Http\Requests;
use App\Http\Controllers\Controller;
use App\Type;
use App\Http\Requests\CreateTypeRequest;
use Auth;

class TypeController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index() {
        $user = Auth::user();
        return response()-> json(['message' => 'Request executed successfully', 'Type'=>Type::all()],200);
//        return response()->json(['data'=>$users], 200);
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param  \Illuminate\Http\CreateTypeRequest  $request
     * @return \Illuminate\Http\Response
     */
public function store(CreateTypeRequest $request)
    {
        $user = Auth::user();
        if (!$user->is_admin){
            return response()->json(['message'=>'Only administrators can create a new type', 'code'=>403],403);
        }
        $values = $request->all();

        $type = Type::create($values);

        return response()->json(['message'=>'Type is now added','code'=>201, 'type' => $type], 201);
    }

     /**
     * Display the specified resource.
     *
     * @param  Type $type
     * @return \Illuminate\Http\Response
     */
    public function show(Type $type)
    {
         return response()->json(['message' =>'Successful retrieval of type.', 'type'=>$type, 'code'=>201], 201);
    }

     /**
     * Update the specified resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  Type  $Type
     * @return \Illuminate\Http\Response
     */
    public function update(Request $request, Type $type)
    {   
        $logon_user = Auth::user();
        if ($logon_user->id != $type->user_id && !$logon_user->is_admin) {            
            return response()->json(['message' => 'You have no access rights to update type','code'=>401], 401);     
        }

        $type->fill($request->all())->save();

        return response()->json(['message'=>'Type updated','type' => $type, 201], 201);
    }

     /**
     * Remove the specified resource from storage.
     *
     * @param  Type  $type
     * @return \Illuminate\Http\Response
     */
    public function destroy(Type $type)
    {
        $logon_user = Auth::user();
        if ($logon_user->id != $type->user_id && !$logon_user->is_admin) {            
            return response()->json(['message' => 'You have no access rights to delete type','code'=>401], 401);
        } 
        $type->delete();
        return response()->json(['message'=>'This type has been deleted','code'=>201], 201);
    }
}
