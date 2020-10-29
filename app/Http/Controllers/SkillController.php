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
        return $skills = Skill::with(['links','tracks.level','user'])->get();        
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function create()
    {
        $user = Auth::user();
        if (!$user->is_admin){
            return response()->json(['message'=>'Only administrators can create a new skill.', 'code'=>403],403);
        }

        return response()->json(['message' => 'Skill create.', 'statuses'=>\App\Status::all(), 'my_tracks'=>$user->tracks, 'public_tracks'=>Track::all(), 'code'=>201]);

    }


    /**
     * Store a newly created resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function store(Request $request)
    {
        $user = Auth::user();
        if (!$user->is_admin){
            return response()->json(['message'=>'Only administrators can create a new skills', 'code'=>403],403);
        }
        $values = $request->except('links');
        $values['user_id'] = $user->id;
        $skill = Skill::create($values);
        if ($request->hasFile('links')) {
            foreach ($request->links as $key=>$link) {
                $timestamp = time();
                $new_link = \App\SkillLink::create(['skill_id'=>$skill->id, 'user_id'=>$user->id, 'status_id'=>4, 'link'=>'videos/skills/'.$timestamp.'.mp4']);

                $file = $link->move(public_path('videos/skills'), $timestamp.'.mp4');
            }
        }
/*        if ($request->hasFile('lesson_link')) {
            $timestamp = time();
            $skill->lesson_link = 'videos/skills/'.$timestamp.'.mp4';

            $file = $request->lesson_link->move(public_path('videos/skills'), $timestamp.'.mp4');

            $skill->save();
        } */
        $skill->tracks()->sync(json_decode($request->track_ids), FALSE);
        return response()->json(['message' => 'Skill correctly added.', 'skill'=>$skill,'links'=>$skill->links,'code'=>201]);

    }
    /**
     * Copy an existing resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function copy($id)
    {
        $user = Auth::user();
        if (!$user->is_admin){
            return response()->json(['message'=>'Only administrators can create a new skills', 'code'=>403],403);
        }
        $skill = Skill::findOrFail($id);
        $newSkill = $skill->replicate();
        $newSkill->user_id = $user->id;
        $newSkill->save();
        foreach ($skill->links as $key=>$link) {
            $new_link = \App\SkillLink::create(['skill_id'=>$newSkill->id, 'user_id'=>$user->id, 'status_id'=>4, 'link'=>$link]);
        }
        foreach ($skill->questions as $key=>$question){
            $new_question = $question;
            $new_question['skill_id'] = $newSkill->id;
            $new_question = \App\Question::create($new_question->toArray());
        }
        $newSkill->tracks()->sync($skill->tracks->pluck('id'), FALSE);
        return response()->json(['message' => 'Skill correctly added.', 'skill'=>$newSkill,'links'=>$newSkill->links, 'tracks'=>$newSkill->tracks, 'questions'=>$newSkill->questions, 'code'=>201]);

    }

    /**
     * Update the specified resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function update(Request $request, Skill $skill)
    {
        $logon_user = Auth::user();
        if ($logon_user->id != $skill->user_id && !$logon_user->is_admin) {            
            return response()->json(['message' => 'You have no access rights to update skill','code'=>401], 401);     
        }
        if ($request->links) {
            foreach ($request->links as $key=>$link) {
                $timestamp = time();
                $new_link = \App\SkillLink::create(['skill_id'=>$skill->id, 'user_id'=>$logon_user->id, 'status_id'=>4, 'link'=>'videos/skills/'.$timestamp.'.mp4']);

                $file = $link->move(public_path('videos/skills'), $timestamp.'.mp4');
            }
        }
        if ($request->remove_links) {
            foreach ($request->remove_links as $key=>$link_id) {
                if ($link_id != -1) {
                   \App\SkillLink::findOrFail($link_id)->delete();
               } else {
                $skill->lesson_link = null;
               }
            }
        }

        if ($request->track_ids){
            $skill->tracks()->sync(json_decode($request->track_ids), FALSE);
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
        return response()->json(['message'=>'Skill fetched.', 'skill'=>$skill, 'links'=>$skill->links,'code'=>201],201);
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