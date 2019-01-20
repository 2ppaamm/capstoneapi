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
    public function index($id)
    {
        $track = Track::find($id);
        if (!$track) {
            return response()->json(['message' => 'This track does not exist', 'code'=>404], 404);
        }
        return response() -> json (['message'=>'Track skills received.','skills' => $track->skills, 'code'=>200], 200);
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function store(CreateSkillRequest $request, Track $tracks)
    {
        $skill = $request->all();
        $new_skill = Auth::user()->skills()->create($skill);
        $tracks->skills()->attach($new_skill->id,['skill_order'=>$tracks->maxSkill($tracks->id)? $tracks->maxSkill($tracks->id)->skill_order + 1:1]);        
        return response()->json(['message' => 'Skill correctly added', 'skill'=>$skill, 'code'=>201]);
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function update(UpdateRequest $request, Track $tracks, Skill $skills)
    {
        $skill = $tracks->skills->find($skills->id);

        if (!$skill) {
            return response()->json(['message'=>'This skill is either not found or is not linked to this track. Cannot update','code'=>404], 404);
        }
        
        $field = $request->get('field');
        $value = $request->get('value');
            $tracks->skills()->updateExistingPivot($skills->id, [$field=>$value]);

        try {
            $tracks->skills()->updateExistingPivot($skills->id, [$field=>$value]);
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
    public function show(Track $tracks, Skill $skills)
    {
        if (!$tracks->skills->find($skills->id)) {
            return response()->json(['message' => 'This skill does not exist in the track.', 'code'=>404], 404);
        }
        return response()->json(['message'=>'Skill retrieved.','skill'=>$skills, 'code'=>200],200);
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

}