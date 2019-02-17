<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

use App\Http\Requests;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Cache;
use App\Skill;
use App\Track;
use App\Http\Requests\UpdateRequest;
use App\Http\Requests\CreateSkillRequest;
use Auth;

class SkillController extends Controller
{

    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index()
    {
        return $skills = Skill::with(['tracks.level','user'])->get();        
    }

    public function create(){
        $user=Auth::user();
        $public_tracks = $user->is_admin ? null: Track::whereStatusId(3)->select('id','track')->get();
        $my_tracks = $user->is_admin? Track::select('id','track')->get():$user->tracks()->select('id','track')->get();

        return response()->json(['statuses'=>\App\Status::select('id','status','description')->get(), 'my_tracks'=>$my_tracks, 'public_tracks'=>$public_tracks, 'level'=>\App\Level::select('id','level')->get()]);
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function store(CreateSkillRequest $request)
    {
        $user = Auth::user();
        if (!$user->is_admin){
            return response()->json(['message'=>'Only administrators can create a new skills', 'code'=>403],403);
        }
        $values = $request->all();
        $values['user_id'] = $user->id;
        $skill = Skill::create($values);
        if ($request->hasFile('lesson_link')) {
            $timestamp = time();
            $skill->lesson_link = 'videos/skills/'.$timestamp.'.mp4';

            $file = $request->lesson_link->move(public_path('videos/skills'), $timestamp.'.mp4');

            $skill->save();
        }
        $skill->tracks()->sync($request->track_ids, FALSE);
        return response()->json(['message' => 'Skill correctly added.', 'skill'=>$skill,'code'=>201]);

    }

    /**
     * Update the specified resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function update(CreateSkillRequest $request, Skill $skill)
    {
        $logon_user = Auth::user();
        if ($logon_user->id != $skill->user_id && !$logon_user->is_admin) {            
            return response()->json(['message' => 'You have no access rights to update skill','code'=>401], 401);     
        }

        if ($request->hasFile('lesson_link')) {
            if (file_exists($skill->lesson_link)) unlink($skill->lesson_link);
            $timestamp = time();
            $skill->lesson_link = 'videos/skills/'.$timestamp.'.mp4';

            $file = $request->lesson_link->move(public_path('videos/skills'), $timestamp.'.mp4');
        } else if ($request->lesson_link){
            $skill->lesson_link = $request->lesson_link;
        }
        if ($request->track_ids){
            foreach (json_decode($request->track_ids) as $track_id) {
               $track = Track::find($track_id);
               $skill->tracks()->sync($track_id,['skill_order'=>$track->maxSkill($track)? $track->maxSkill($track)->skill_order + 1:1], FALSE);
            }        
        }

        $skill->fill($request->except('lesson_link','track_id'))->save();

        return response()->json(['message'=>'skill updated','skill' => $skill, 'code'=>201], 201);
    }

    /**
     * Display the specified resource.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function show(Skill $skill)
    {
        return response()->json(['message'=>'Skill fetched.', 'skill'=>$skill, 'code'=>201],201);
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function destroy(Request $request, Skill $skill)
    {
        if(sizeof($skill->questions) > 0)
        {
            return response()->json(['message'=>'There are questions in this skill. Delete all questions first.'], 409);
        }

        // check if user wants to delink all the tracks
        $request->delink_tracks ? $skill->tracks()->detach():null;

        if(sizeof($skill->tracks) > 0)
        {
            return response()->json(['message'=>'There are tracks that uses this skill. Do you want to delink all the tracks?', 'code'=>'delink_tracks'], 409);
        }
        $skill->delete();
        return response()->json(['message'=>'Skill has been deleted.'], 200);
    }

    public function usersPassed($id) {
        $skill = Skill::findOrFail($id);
        return response()->json(['message'=>'Users who passed/attempted/failed this skill.','passed'=>$skill->users()->wherePivot('skill_passed','=',TRUE)->get(),'failed'=>$skill->users()->wherePivot('skill_passed','=',FALSE)->wherePivot('noOfFails','<',4)->get(),'attempted'=>$skill->users()->wherePivot('skill_passed','=',FALSE)->wherePivot('noOfFails','<',4)->get(),'code'=>201], 201);
        
    }

    public function search(Request $request)
    {
        $skills = null;
        if ($request->track){
            $skills = Cache::remember('skills', 15/60, function() use ($request) {
                   return Track::find($request->track)->skills()->with('questions','tracks','users')->get();
                });
        }
        if ($request->level){
            $skills = Cache::remember('skills',15/60, function() use ($request){
            return Skill::with('questions','tracks','users')->whereHas('tracks', function ($query) use ($request) {
                       $query->whereIn('id', \App\Level::find($request->level)->tracks()->pluck('id')->toArray());
                        })->get();

            });
        }
        if ($request->keyword){
            $skills = Cache::remember('skills',15/60, function() use ($request){
            return Skill::with('questions','tracks','users')->where('description','LIKE','%'.$request->keyword.'%')->get();});
        }

        return response()->json(['skills'=>$skills], 200);
    }
}