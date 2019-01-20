<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

use App\Http\Requests;
use App\Http\Controllers\Controller;
use App\House;
use App\Course;
use Auth;
use App\Http\Requests\CreateHouseRequest;

class CourseHouseController extends Controller
{
    public function __construct(){
    }
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index(Course $course)
    {
        return response() -> json (['houses'=>$course->houses()->with('created_by')->get(),'message'=>'Classes retrieved for Course '.$course->course.'.','code'=>201], 201);
    }

    /**
     * Show the form for creating a new resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function create()
    {
        //
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function store(CreateHouseRequest $request, Course $course)
    {
        $values = $request->except('image');
        $user = Auth::user();
        $values['user_id'] = $user->id;
        $timestamp = time();
        
        if ($request->hasFile('image')) {
            $values['image'] = 'images/houses/'.$timestamp.'.png';
            $request->image->move(public_path('images/houses'), $timestamp.'.png');
        } else if (file_exists($course->image)) copy($course->image, public_path('images/houses'.$timestamp.'.png'));
     //enrol user to the house in house_role_user
        $house = House::create($values);
        $house->enrolledusers()->attach($user, ['role_id'=>4]);

      //find course, move image and create tracks
        $tracks = $course->tracks;

        for ($i=0; $i<sizeof($tracks); $i++) {
            $house->tracks()->attach($tracks[$i],['track_order'=>$tracks[$i]->pivot->track_order]);
        }

        return response()->json(['message'=>$house->house . ' is now added as a new class to '. $course->course.'.','house'=>$house,'code'=>201], 201);
    }

    /**
     * Display the specified resource.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function show(Course $course, House $house)
    {
        return response()->json(['message'=>'Displaying class', 'enrolled'=>$house->enrolledusers, 'tracks'=>$house->tracks,'code'=>201],201);
    }

    /**
     * Show the form for editing the specified resource.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function edit(Course $course, House $house)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function update(Course $course, Request $request)
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
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function destroy($id)
    {
        //
    }
}
