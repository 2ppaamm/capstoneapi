<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

use App\Http\Requests;
use App\Http\Controllers\Controller;
use App\Track;
use App\Http\Requests\CreateTrackRequest;
use App\Course;
use App\Http\Requests\UpdateRequest;
use Auth;

class TrackController extends Controller
{
    public function __construct(){
    }

    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index()
    {
        return $tracks = Track::with('skills')
        ->with('level')->with('field')->with('status')->with('houses')
        ->select('id','track','description','field_id', 'level_id','status_id')->get();        
    }

    public function create(){
        $user=Auth::user();
        $public_tracks = $user->is_admin ? Track::select('id','track'):Track::whereStatusId(3)->select('id','track')->get();
        $my_tracks = $user->tracks()->select('id','track')->get();

        return response()->json(['message'=>'Fields for create track fetched.','levels'=> \App\Level::select('id','level','description')->get(), 'statuses'=>\App\Status::select('id','status','description')->get(),'fields'=>\App\Field::select('id','field','description')->get(), 'my_tracks'=>$my_tracks, 'public_tracks'=>$public_tracks,'skills'=>\App\Skill::select('id','skill','description')->get(),'code'=>200], 200);
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function store(CreateTrackRequest $request)
    {
        $user = Auth::user();
        if (!$user->is_admin){
            return response()->json(['message'=>'Only administrators can create a new courses', 'code'=>403],403);
        }
        $values = $request->except('skill_ids');
        $values['user_id'] = $user->id;
        $track = Track::create($values);
        if ($request->skills_ids){
            foreach ($request->skill_ids as $skill_id) {
               $skill = \App\Skill::find($skill_id);
               $skill->tracks()->sync($track->id,['skill_order'=>$track->maxSkill($track)? $track->maxSkill($track)->skill_order + 1:1], FALSE);
            }
        }
        return response()->json(['message' => 'Track correctly added.', 'track'=>$track,'code'=>201]);
    }

    /**
     * Display the specified resource.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function show(Track $track)
    {
        if (!$track) {
            return response()->json(['message' => 'This track does not exist.', 'code'=>404], 404);
        }

        return response()->json(['message'=>'Track with skills.','tracks'=>$track,'skills'=>$track->skills,'code'=>201],201);
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function update(Request $request, Track $track)
    {
        $logon_user = Auth::user();
        if ($logon_user->id != $track->user_id && !$logon_user->is_admin) {            
            return response()->json(['message' => 'You have no access rights to update track','code'=>401], 401);     
        }
        $track->fill($request->all())->save();
        $track->skills()->sync($request->skill_id, true);
        
        return response()->json(['message'=>'Track updated','track' => $track, 'code'=>201], 201);
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function destroy(Request $request, Track $track)
    {
        $logon_user = Auth::user();
        if ($logon_user->id != $track->user_id && !$logon_user->is_admin) {            
            return response()->json(['message' => 'You have no access rights to delete track','code'=>401], 401);   
        }  
        if ($request->delink_skills){
            $track->skills()->detach();
        }        

        if(sizeof($track->skills) > 0){
            return response()->json(['message'=>'There are skills belonging to this track. Do you want to delink them?','skills'=>$track->skills,'code'=>'delink_skills'], 409);            
        }
        if(sizeof($track->courses)>0 || sizeof($track->houses)>0){
            return response()->json(['message'=>'This track belongs to a class or course. You will need to go to the course or class to delink them first','classes'=>$track->houses, 'courses'=>$track->courses,'code'=>409], 409);
        }
        $track->delete();
        return response()->json(['message'=>'Track has been deleted.'], 200);
    }
}