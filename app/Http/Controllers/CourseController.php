<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

use App\Http\Requests;
use App\Http\Controllers\Controller;
use App\Course;
use App\Http\Controllers\CourseTrackController;
use App\Http\Requests\CreateCourseRequest;
use Auth;
use Config;

class CourseController extends Controller
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
        return $courses = Course::with('tracks.skills','houses.created_by','status','houses')->get();
        return response()-> json(['message' => 'Request executed successfully', 'courses'=>$courses],200);
    }
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function open()
    {
        return $courses = Course::with('tracks.skills','validHouses.created_by')->get();
    }

    /**
     * Copy to a new resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function copy(CreateCourseRequest $request, Course $course)
    {
        $new_course = $course->replicate();
        $new_course->fill($request->except('image'))->save();
        $tracks=$course->tracks;
        for ($i=0; $i<sizeof($tracks); $i++) {
            $new_course->tracks()->attach($tracks[$i],['order'=>$tracks[$i]->pivot->order]);
        }
        $controller = new CourseTrackController;
        return $controller->index($new_course->id);
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
            return response()->json(['message'=>'Only administrators can create a new courses', 'code'=>403],403);
        }
        $values = $request->except('image');
        $values['user_id'] = $user->id;
        if ($request->hasFile('image')) {
            $timestamp = time();
            $values['image'] = 'images/courses/'.$timestamp.'.png';

            $file = $request->image->move(public_path('images/courses'), $timestamp.'.png');
        } 

        $course = Course::create($values);

        return response()->json(['message'=>'Course is now added','code'=>201, 'course' => $course], 201);
    }

    /**
     * Display the specified resource.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function show(Course $course)
    {
        $course = Course::with(['status', 'tracks.skills','validHouses'])->find($course->id);
 /*        $course = Course::with(['tracks'=> function ($query){  
            $query -> with('unit')
                ->select('description','id','track','level_id')->with('level')
                ->with(['skills' => function ($query) {
                  $query->select('track_id','skill')->orderBy('skill_order');}])
                ->orderBy('track_order'); 
            }])->find($course->id);
*/
        if (!$course) {
            return response()->json(['message' => 'This course does not exist', 'code'=>404], 404);
        }
        return response()->json(['course'=>$course, 'statuses'=>\App\Status::select('id','status','description')->get(),'code'=>201], 201);
    }


    /**
     * Update the specified resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  Course  $course
     * @return \Illuminate\Http\Response
     */
    public function update(Request $request, Course $course)
    {   
        $logon_user = Auth::user();
        if ($logon_user->id != $course->user_id && !$logon_user->is_admin) {            
            return response()->json(['message' => 'You have no access rights to update course','code'=>401], 401);     
        }

        if ($request->hasFile('image')) {
            if (file_exists($course->image)) unlink($course->image);
            $timestamp = time();
            $course->image = 'images/courses/'.$timestamp.'.png';

            $file = $request->image->move(public_path('images/courses'), $timestamp.'.png');
        } 

        $course->fill($request->except('image'))->save();

        return response()->json(['message'=>'Course updated','course' => $course, 201], 201);
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  Course  $course
     * @return \Illuminate\Http\Response
     */
    public function destroy(Course $course)
    {
        $logon_user = Auth::user();
        if ($logon_user->id != $course->user_id && !$logon_user->is_admin) {            
            return response()->json(['message' => 'You have no access rights to delete course','code'=>401], 401);
        } 
        if (sizeof($course->houses)>0){
            return response()->json(['message'=>'There are classes based on this course. Delete those classes first.','code'=>500],500);
        }
        $course->delete();
        return response()->json(['message'=>'Course '.$course->name.' is deleted','code'=>201], 201);
    }
}