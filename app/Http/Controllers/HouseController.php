<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

use App\Http\Requests;
use App\Http\Controllers\Controller;
use App\House;
use App\Course;
use App\Http\Requests\CreateHouseRequest;
use Auth;
use App\Http\Requests\UpdateRequest;

class HouseController extends Controller
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
        return $houses = House::with(['enrolment','tracks.skills','created_by'])->select('id','house','description','start_date','end_date','image')->get();
    }

    public function create()
    {
        return response()->json(['courses'=>Course::select('id','course')->get(),'currency'=>['USD','SGD'],'code'=>201],201);
    }
    /**
     * Store a newly created resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function store(CreateHouseRequest $request)
    {
        $values = $request->except('image');
        $user = Auth::user();
        $values['user_id'] = $user->id;
        $timestamp = time();
        $course = Course::find($request->course_id);
        
        if ($request->hasFile('image')) {
            $values['image'] = 'images/houses/'.$timestamp.'.png';
            $request->image->move(public_path('images/houses'), $timestamp.'.png');
        } else if ($course && file_exists($course->image)) copy($course->image, public_path('images/houses'.$timestamp.'.png'));
     //enrol user to the house in house_role_user
        $house = House::create($values);
        $house->enrolledusers()->attach($user, ['role_id'=>4]);


      //find course, move image and create tracks
        if ($request->link_tracks){
            $tracks = Course::find($request->course_id)->tracks;
            for ($i=0; $i<sizeof($tracks); $i++) {
                $house->tracks()->attach($tracks[$i],['track_order'=>$tracks[$i]->pivot->track_order]);
            }
        }

//        $controller = new DashboardController;

        return response()->json(['message'=>$house->house . ' is now added as a new class.','code'=>201, 'class'=>$house], 201);
    }

    /**
     * Display the specified resource.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function show(House $house)
    {
        return response()->json(['message'=> 'Class is as displayed.', 'code'=>201, 'house'=>$house],201);
    }


    /**
     * Update the specified resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function update(Request $request, House $house)
    {
        $values = $request->except('image');
        $user = Auth::user();
        $timestamp = time();
        $course = Course::find($request->course_id);
        
        if ($request->hasFile('image')) {
            if (file_exists($house->image)) unlink($house->image);
            $house->image = 'images/houses/'.$timestamp.'.png';

            $file = $request->image->move(public_path('images/houses/'), $timestamp.'.png');
        } 
     //enrol user to the house in house_role_user
        $house->fill($request->except('image'))->save();
        $house->tracks()->sync($request->tracks);
        return response()->json(['message'=>$house->house.' updated successful', 'class'=>$house, 'code'=>201], 201);
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function destroy(House $house)
    {
        if (count($house->tracks) > 0 ) return response()->json(['message'=>'There are tracks in the class, cannot delete', 'code'=>404], 404);
        else if (count($house->enrolledusers) > 2) return response()->json(['message'=>'There are users in the class, cannot delete','code'=>404], 404);
            else {
                try {$house->delete();} 
                catch (\Exception $exception) { return response()->json(['message'=>'Class cannot be deleted', 'code'=>404], 404);}
            }
        return response()->json(['message'=>'Class deleted successfully', 'code'=>200],200);
    }
}