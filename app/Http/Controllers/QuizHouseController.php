<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use \App\House;
use \App\Quiz;

class QuizHouseController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index(Quiz $quiz)
    {
        return response() -> json (['message'=>'Quiz classes all received.','class' => $quiz->houses, 'code'=>200], 200);
    }

    public function list_quizzes(House $house)
    {
        return response() -> json (['message'=>'House quizzes received.','quizzes' => $house->quizzes, 'code'=>200], 200);
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function store(Request $request, Quiz $quiz)
    {
        foreach ($request->all() as $house_id){
            if ($house = House::find($house_id)){
                $house->quizzes()->sync($quiz->id, false);
            } else {
                response()->json(['message'=>'Error in house chosen'], 401);
            } 
        }
        return response()->json(['message' => 'House(s) correctly added', 'quiz'=>$quiz, 'code'=>201]);
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function update(UpdateRequest $request, Quiz $quiz, House $house)
    {
        $house = $quiz->houses->find($house->id);

        if (!$house) {
            return response()->json(['message'=>'This house is either not found or is not linked to this quiz. Cannot update','code'=>404], 404);
        }
        
        $field = $request->get('field');
        $value = $request->get('value');
            $quiz->houses()->sync([$house->id=>[$field=>$value]]);

        try {
            $quiz->houses()->sync([$house->id=>[$field=>$value]]);
        }
        catch(\Exception $exception){
            return response()->json(['message'=>'Update of house in the quiz failed!','code'=> $exception->getCode()]);
        }

        return response()->json(['message'=>'Updated house for this quiz','house'=>$house,'code'=>200],200);
    }

    /**
     * Display the specified resource.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function show(Quiz $quiz, House $house)
    {
        if (!$quiz->houses->find($house->id)) {
            return response()->json(['message' => 'This house does not exist in the quiz.', 'code'=>404], 404);
        }
        return response()->json(['message'=>'House retrieved.','house'=>$house, 'code'=>200],200);
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function destroy(Quiz $quiz, House $house)
    {
        $houses = $quiz->houses->find($house->id);
        if (!$houses) {
            return response()->json(['message' => 'This house does not exist for this quiz', 'code'=>404], 404);
        }
        $quiz->houses()->detach($house->id);
        return response()->json(['message'=>'House has been removed from this quiz.', 'houses'=>$quiz->houses, 'code'=>201], 201);
    }

    public function deleteHouses(Quiz $quiz){
        $quiz->houses()->detach();
        return response()->json(['message'=>'All houses are deleted','quiz'=>$quiz, 'code'=>201],201);
    }

}
