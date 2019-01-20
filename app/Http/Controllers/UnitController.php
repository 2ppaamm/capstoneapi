<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

use App\Http\Requests;
use App\Http\Controllers\Controller;
use App\Unit;
use App\Http\Requests\CreateUnitRequest;
use Auth;

class UnitController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index()
    {
        $user = Auth::user();
        return response()-> json(['message' => 'Request executed successfully', 'units'=>Unit::all()],200);

        //return response()->json(['levels'=>$levels],200);
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param  \Illuminate\Http\CreateUnitRequest  $unit
     * @return \Illuminate\Http\Response
     */
    public function store(CreateUnitRequest $request)
    {
        $user = Auth::user();
        if (!$user->is_admin){
            return response()->json(['message'=>'Only administrators can create a new unit', 'code'=>403],403);
        }
        $values = $request->all();

        $unit = Unit::create($values);

        return response()->json(['message'=>'Unit is now added','code'=>201, 'unit' => $unit], 201);
    }

    /**
     * Display the specified resource.
     *
     * @param  Unit $unit
     * @return \Illuminate\Http\Response
     */
    public function show(Unit $unit)
    {
        return response()->json(['message' =>'Successful retrieval of unit.', 'unit'=>$unit, 'code'=>201], 201);
    }

 /**
     * Update the specified resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  Unit  $unit
     * @return \Illuminate\Http\Response
     */
    public function update(Request $request, Unit $unit)
    {   
        $logon_user = Auth::user();
        if ($logon_user->id != $unit->user_id && !$logon_user->is_admin) {            
            return response()->json(['message' => 'You have no access rights to update unit','code'=>401], 401);     
        }

        $unit->fill($request->all())->save();

        return response()->json(['message'=>'Unit updated','unit' => $unit, 201], 201);
    }

     /**
     * Remove the specified resource from storage.
     *
     * @param  Unit  $unit
     * @return \Illuminate\Http\Response
     */
    public function destroy(Unit $unit)
    {
        $logon_user = Auth::user();
        if (!$logon_user->is_admin) {            
            return response()->json(['message' => 'You have no access rights to delete unit','code'=>401], 401);
        } 
        $unit->delete();
        return response()->json(['message'=>'This unit has been deleted','code'=>201], 201);
    }
}
