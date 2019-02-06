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
        return response() -> json (['message'=>'Track skills received.','skill' => $track->skills, 'code'=>200], 200);
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function store(CreateSkillRequest $request, Track $track)
    {
        $values = $request->all();
        $skill = Auth::user()->skills()->create($values);
        $skill->tracks()->attach($track->id,['skill_order'=>$track->maxSkill($track->id)? $track->maxSkill($track->id)->skill_order + 1:1]);        
        return response()->json(['message' => 'Skill correctly added', 'skill'=>$skill, 'code'=>201]);
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
            $track->skills()->updateExistingPivot($skill->id, [$field=>$value]);

        try {
            $track->skills()->updateExistingPivot($skill->id, [$field=>$value]);
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

    public function deleteAll(Track $track){
        $track->skills()->detach();
        return response()->json(['message'=>'All skills are deleted','track'=>$track, 'code'=>201],201);
    }

}