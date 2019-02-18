<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

use App\Http\Requests;
use App\Http\Controllers\Controller;
use App\Course;
use App\Track;
use App\Http\Requests\CreateTrackRequest;
use Auth;
use App\Http\Requests\UpdateRequest;

class CourseTrackController extends Controller
{
    public function __construct(){
    }

    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index(Course $courses)
    {
        $tracks = $courses->tracks()->with('level')->with('skills')->with('unit')
            ->orderBy('track_order')
            ->get();

        return response()->json(['message'=>'Course tracks retrieved','code'=>201, 'tracks'=>$tracks],201);
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function store(CreateTrackRequest $request, Course $courses)
    {
        $new_track = Auth::user()->tracks()->create($request->all());
        $courses->tracks()->attach($new_track->id,['track_order'=>$courses->maxTrack($courses->id)? $courses->maxTrack($courses->id)->track_order + 1:1]);
        return response()->json(['message' => 'Track correctly added', 'track'=>$new_track,'code'=>201]);
    }

    /**
     * Display the specified resource.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function show(Course $courses, Track $tracks)
    {
        if (!$courses->tracks->find($tracks->id)) {
            return response()->json(['message' => 'This track does not exist in the course.', 'code'=>404], 404);
        }

        try {
            $tracks = $courses->tracks()->with('skills.questions')->whereId($tracks->id)->first();
        }
        catch(\Exception $exception){
            return response()->json(['message'=>'Cannot find track or course! ', 'code'=>404],404);
        }

        return response()->json(['message'=>'Track retrieved', 'track'=>$tracks,'code'=>200], 200);
    }


    /**
     * Update the specified resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function update(UpdateRequest $request, Course $courses, Track $tracks)
    {        
        $msg =  $courses->tracks()->sync([$tracks->id=>[$request->get('field')=>$request->get('value')]]) ? $request->field." successfully Updated" : "The track does not belong to the course.";
        return response()->json(['message'=>$msg, 'track'=>$courses->tracks->find($tracks->id), 'code'=>201], 201);
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function destroy(Course $courses, Track $tracks)
    {
        if (!$courses->tracks->find($tracks->id)) {
            return response()->json(['message'=>'This track is either not found or is not linked to this course. Cannot delete','code'=>404], 404);
        }

        $courses->tracks()->detach($tracks->id);

        return response()->json(['message'=>'Track deleted from course.','course'=>$courses->tracks,'code'=>201], 201);
    }
}