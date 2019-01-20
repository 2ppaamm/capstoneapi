<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

use App\Http\Requests;
use App\Http\Controllers\Controller;
use App\Permission;
use App\Http\Requests\CreatePermissionRequest;
use Auth;

class PermissionController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index()
    {
        $user = Auth::user();
        return response()-> json(['message' => 'Request executed successfully', 'permissions'=>Permission::all()],200);

        //return response()->json(['levels'=>$levels],200);
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param  \Illuminate\Http\CreatePermissionRequest  $request
     * @return \Illuminate\Http\Response
     */
    public function store(CreatePermissionRequest $request)
    {
        $user = Auth::user();
        if (!$user->is_admin){
            return response()->json(['message'=>'Only administrators can create a new permission', 'code'=>403],403);
        }
        $values = $request->all();

        $permission = Permission::create($values);

        return response()->json(['message'=>'Permission is now added','code'=>201, 'permission' => $permission], 201);
    }

    /**
     * Display the specified resource.
     *
     * @param  Permission $permission
     * @return \Illuminate\Http\Response
     */
    public function show(Permission $permission)
    {
        return response()->json(['message' =>'Successful retrieval of permission.', 'permission'=>$permission, 'code'=>201], 201);
    }

 /**
     * Update the specified resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  Permission  $permission
     * @return \Illuminate\Http\Response
     */
    public function update(Request $request, Permission $permission)
    {   
        $logon_user = Auth::user();
        if ($logon_user->id != $permission->user_id && !$logon_user->is_admin) {            
            return response()->json(['message' => 'You have no access rights to update permission','code'=>401], 401);     
        }

        $permission->fill($request->all())->save();

        return response()->json(['message'=>'Permission updated','permission' => $permission, 201], 201);
    }

     /**
     * Remove the specified resource from storage.
     *
     * @param  Permission  $permission
     * @return \Illuminate\Http\Response
     */
    public function destroy(Permission $permission)
    {
        $logon_user = Auth::user();
        if ($logon_user->id != $permission->user_id && !$logon_user->is_admin) {            
            return response()->json(['message' => 'You have no access rights to delete permission','code'=>401], 401);
        } 
        $permission->delete();
        return response()->json(['message'=>'This permission has been deleted','code'=>201], 201);
    }
}
