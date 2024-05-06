<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

use App\Http\Requests;
use App\Enrolment;
use App\Http\Requests\CreateMasterCodeRequest;
use Auth;
use App\User;
use App\Role;

class MasterCodeController extends Controller
{
    public function __construct(){
        //Auth::login(User::find(2));
    }
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index()
    {
        return Enrolment::where('mastercode', '>', 0)->get();
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function store(CreateMasterCodeRequest $request)
    {
        $user = ($request->user_id && Auth::user()->is_admin) ? User::find($request->user_id) : Auth::user();
        $allocation_success = FALSE;
        $places_alloted = $request->places_alloted ? $request->places_alloted:1;
        $purchaser_id = $request->purchaser_id ? $request->purchaser_id : $user->id;
        $payment_email = $request->payment_email ? $request->payment_email : $user->email;
        $role_id = $request->role_id ? $request->role_id : Role::where('role', 'LIKE', 'Parent')->first()->id;
        while (!$allocation_success) {
            $mastercode = rand (100000 , 999999 );
            $enrolment = Enrolment::firstOrNew(['user_id'=>$request->user_id, 'role_id'=>$role_id, 'house_id'=>$request->house_id]);
            $allocation_success =$enrolment->fill(['mastercode'=>$mastercode, 'places_alloted'=>$places_alloted, 'purchaser_id'=>$purchaser_id, 'payment_email'=>$payment_email])->save();
        }
        return $enrolment;
    }
    /**->
     * Display the specified resource.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function show($code)
    {
        return $enrolment = Enrolment::whereMastercode($code)->first();
    }


    /**
     * Update the specified resource in storage.
     * You cannot updaet a code. You can only destroy and re-create
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function update(Request $request, $id)
    {
        //
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function destroy($code)
    {
        $enrolment = Enrolment::whereMastercode($code)->first();
        if (!$enrolment) {
            return response()->json(['message'=>'This code does not exist', 'code'=>404], 404);
        }
        $enrolment->update(['mastercode'=>null]);
        return $enrolment;        
    }
}
