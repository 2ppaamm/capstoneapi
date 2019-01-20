<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

use App\Http\Requests;
use App\Http\Controllers\Controller;
use App\Role;
use App\Http\Requests\CreateRoleRequest;
use Auth;


class RoleController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index()
    {
        $user = Auth::user();
        return response()-> json(['message' => 'Request executed successfully', 'roles'=>Role::all()],200);

        //return response()->json(['levels'=>$levels],200);
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param  \Illuminate\Http\CreateRoleRequest  $role
     * @return \Illuminate\Http\Response
     */
    public function store(CreateRoleRequest $request)
    {
        $user = Auth::user();
        if (!$user->is_admin){
            return response()->json(['message'=>'Only administrators can create a new role', 'code'=>403],403);
        }
        $values = $request->all();

        $role = Role::create($values);

        return response()->json(['message'=>'Role is now added','code'=>201, 'role' => $role], 201);
    }

    /**
     * Display the specified resource.
     *
     * @param  Role $role
     * @return \Illuminate\Http\Response
     */
    public function show(Role $role)
    {
        return response()->json(['message' =>'Successful retrieval of role.', 'role'=>$role, 'code'=>201], 201);
    }

 /**
     * Update the specified resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  Role  $role
     * @return \Illuminate\Http\Response
     */
    public function update(Request $request, Role $role)
    {   
        $logon_user = Auth::user();
        if ($logon_user->id != $role->user_id && !$logon_user->is_admin) {            
            return response()->json(['message' => 'You have no access rights to update role','code'=>401], 401);     
        }

        $role->fill($request->all())->save();

        return response()->json(['message'=>'Role updated','role' => $role, 201], 201);
    }

     /**
     * Remove the specified resource from storage.
     *
     * @param  Role  $role
     * @return \Illuminate\Http\Response
     */
    public function destroy(Role $role)
    {
        $logon_user = Auth::user();
        if (!$logon_user->is_admin) {            
            return response()->json(['message' => 'You have no access rights to delete role','code'=>401], 401);
        } 
        $role->delete();
        return response()->json(['message'=>'This role has been deleted','code'=>201], 201);
    }
}
