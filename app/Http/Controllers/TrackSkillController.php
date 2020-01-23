<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

use App\Http\Requests;
use App\Http\Controllers\Controller;
use App\Track;
use App\Http\Requests\CreateSkillRequest;
use App\Skill;
use App\Http\Requests\UpdateRequest;
use Auth;

class TrackSkillController extends Controller
{
    public function __construct(){
    }
    
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index(Track $track)
    {
        return response() -> json (['message'=>'Track skills all received.','skill' => $track->skills()->with('links')->get(), 'code'=>200], 200);
    }

    public function list_tracks(Skill $skill)
    {
        return response() -> json (['message'=>'Skill tracks received.','tracks' => $skill->tracks, 'code'=>200], 200);
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function store(Request $request, Track $track)
    {
        foreach ($request->all() as $skill_id){
            if ($skill = Skill::find($skill_id)){
                $skill->tracks()->sync($track->id, false);
            } else {
                response()->json(['message'=>'Error in skill chosen'], 401);
            } 
        }
        return response()->json(['message' => 'Skill(s) correctly added', 'skill'=>$skill, 'code'=>201]);
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function update(UpdateRequest $request, Track $track, Skill $skill)
    {
        $skill = $track->skills->find($skill->id);

        if (!$skill) {
            return response()->json(['message'=>'This skill is either not found or is not linked to this track. Cannot update','code'=>404], 404);
        }
        
        $field = $request->get('field');
        $value = $request->get('value');
            $track->skills()->sync([$skill->id=>[$field=>$value]]);

        try {
            $track->skills()->sync([$skill->id=>[$field=>$value]]);
        }
        catch(\Exception $exception){
            return response()->json(['message'=>'Update of skill in the track failed!','code'=> $exception->getCode()]);
        }

        return response()->json(['message'=>'Updated skill for this track','skill'=>$skill,'code'=>200],200);
    }

    /**
     * Display the specified resource.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function show(Track $track, Skill $skill)
    {
        if (!$track->skills->find($skill->id)) {
            return response()->json(['message' => 'This skill does not exist in the track.', 'code'=>404], 404);
        }
        return response()->json(['message'=>'Skill retrieved.','skill'=>$skill, 'code'=>200],200);
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function destroy(Track $track, Skill $skill)
    {
        $skills = $track->skills->find($skill->id);
        if (!$skills) {
            return response()->json(['message' => 'This skill does not exist for this track', 'code'=>404], 404);
        }
        $track->skills()->detach($skill->id);
        return response()->json(['message'=>'Skill has been removed from this track.', 'skills'=>$track->skills()->with('user')->get(), 'code'=>201], 201);
    }

    public function deleteSkills(Track $track){
        $track->skills()->detach();
        return response()->json(['message'=>'All skills are deleted','track'=>$track, 'code'=>201],201);
    }

    public function deleteTracks(Skill $skill){
        $skill->tracks()->detach();
        return response()->json(['message'=>'All tracks are deleted','skill'=>$skill, 'code'=>201],201);
    }

}