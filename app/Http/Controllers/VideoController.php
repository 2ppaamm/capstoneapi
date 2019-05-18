<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Video as Video;

class VideoController extends Controller
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
    	return "hello";
        return $videos = Video::all();
    }

   /**
     * Store a newly created resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function store(CreateVideoRequest $request)
    {
        $user = Auth::user();
        if (!$user->teachingHouses || !$user->is_admin){
            return response()->json(['message'=>'Only teachers or administrators can create a new video.', 'code'=>403],403);
        }
        $video = $request->except('video_link');
        $video['user_id'] = $user->id;
        if ($request->hasFile('video_link')) {
            $timestamp = time();
            $video['video_link'] = 'videos/skills/'.$timestamp.'.mp4';
            $file = $link->move(public_path('videos/skills'), $timestamp.'.mp4');
        }
        $video = Video::create($values);
        return response()->json(['message' => 'Video correctly added.', 'code'=>201]);

    }

    /**
     * Update the specified resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function update(CreateVideoRequest $request, Video $video)
    {
        $logon_user = Auth::user();
        if ($logon_user->id != $video->user_id && !$logon_user->is_admin) {            
            return response()->json(['message' => 'You have no access rights to update skill','code'=>401], 401);     
        }
        if ($request->video_link) {
            $timestamp = time();
            $request->video_link->move(public_path('videos/skills'), $timestamp.'.mp4');
            $video->video_link ='videos/skills/'.$timestamp.'.mp4';
        }
        $video->fill($request->except('video_link'))->save();


        return response()->json(['message'=>'Video updated','video' => $video, 'code'=>201], 201);
    }

    /**
     * Display the specified resource.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function show(Video $video)
    {
        return response()->json(['message'=>'Video fetched.', 'video'=>$video,'code'=>201],201);
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function destroy(Video $video)
    {
        $logon_user = Auth::user();
        if ($logon_user->id != $video->user_id && !$logon_user->is_admin) {            
            return response()->json(['message' => 'You have no access rights to update skill','code'=>401], 401);     
        }
        $video->delete();
        return response()->json(['message'=>'Video has been deleted.'], 200);
    }
}
