<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

use App\Http\Requests;
use App\Http\Controllers\Controller;
use App\House;
use App\Http\Requests\CreateHouseTrackRequest;
use Auth;
use App\Http\Requests\UpdateRequest;
use App\Track;

class HouseTrackController extends Controller
{
    public function __construct(){
    }

	   /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
 
	public function index(House $house){
        $house = $house->tracks()->select('description','id','status_id','track','level_id')->with(['level'=>function($query){$query->select('id','description');}])->with(['status'=>function($query){$query->select('id','status');}])
                //->with(['skills' => function ($query) {
                //$query->select('id','skill')->orderBy('skill_order');}])
                ->orderBy('pivot_track_order')->get();
        if (!$house) {
            return response()->json(['message' => 'This class does not exist', 'code'=>404], 404);
        }

        return response()->json(['message' => 'Class tracks listed', 'class'=>$house,'code'=>201], 201);
    }

   /**
     * Store a newly created resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function store(Request $request, House $house)
    {
/* when ready
        foreach ($request->track as $track_id=>$linktrack){
            if ($track=Track::find($track_id)){
                $track->houses()->sync([$house->id=>$linktrack], false);
            } else {
                response()->json(['message'=>'Error in track chosen'], 401);
            } 
        }
*/
        $house->tracks()->sync($request->all(), false);

        return response()->json(['message' => 'Track(s) correctly added to house', 'tracks added'=>$house->tracks,'code'=>201]);
    }

    /**
     * Display the specified resource.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function show(House $house, Track $track)
    {
        return $house->tracks()->with('skills.questions')->whereTrackId($track->id)->get();   
    }


    /**
     * Update the specified resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function update(Request $request, House $house, Track $track)
    {
        $user = Auth::user();
        $house->tracks()->sync([$track->id=>$request->all()], false);
        return response()->json(['message'=>$house->house.' tracks updated successful', 'class'=>$house, 'code'=>201], 201);
    }


    /**
     * Remove the tracks from the class.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function destroy(House $house, Track $track)
    {
        $logon_user = Auth::user();
        if ($logon_user->id != $house->user_id && !$logon_user->is_admin) {            
            return response()->json(['message' => 'You have no access rights to delete track','code'=>401], 401);   
        }  
        try {
            $house->tracks()->detach($track);
            $tracks=$house->tracks()->with(['owner','skills.user','field','status','level'])->get();
        } catch(\Exception $exception){
            return response()->json(['message'=>'Unable to remove track from class', 'code'=>500], 500);
        }
        return response()->json(['message'=>'Track removed successfully','tracks'=>$tracks, 'code'=>201],201);
    }

    public function deleteAll(House $house){
        $house->tracks()->detach();
        return response()->json(['message'=>'All tracks are deleted','house'=>$house, 'code'=>201],201);
    }
}