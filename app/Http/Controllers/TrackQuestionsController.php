<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Track;
use App\Question;
use App\Skill;
use Auth;

class TrackQuestionsController extends Controller
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
    	$user = Auth::user();
    	return $user->skillspassed()->get();
    	return $user->skilluser;
    	//return $track->skill;
        $questions = $skill->questions;

        if (sizeof($questions) <1) {
            return response() ->json(['message' => 'There is no question needed for this skill.', 'code'=>404], 404);
        }

        return response() -> json (['message'=>'Questions from skill fetched.','questions' => $questions], 200);
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param  \Illuminate\Http\CreateQuestionRequest  $request
     *         $skillId
     * @return \Illuminate\Http\Response
     */
    public function store(CreateQuestionRequest $request, Skill $skills)
    {
        $question = $request->all();
        $question['skill_id'] = $skills->id;
        $new_question = Auth::user()->questions()->create($question);
        return response()->json(['message' => 'Question correctly added', 'question'=>$new_question,'code'=>201], 201);
    }

    /**
     * Display the specified resource.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function show(Skill $skills, Question $questions)
    {
        $question = $skills->questions->find($questions->id);
        if (!$question) {
            return response()->json(['message' => 'This question does not exist for this skill', 'code'=>404], 404);
        }
        return response() -> json (['message'=>'Question fetched', 'question' => $questions, 'code'=>200], 200);
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function update(UpdateRequest $request, Skill $skills, Questions $questions)
    {

        $question = $skills->questions->find($questions->id);
        if (!$question) {
            return response()->json(['message' => 'This question does not exist for this skill', 'code'=>404], 404);
        }

        $field = $request->get('field');
        $value = $request->get('value');
        $question->$field = $value;
        $question->save();

        return response() -> json (['message'=>'Updated question', 'question' => $question, 'code'=>200], 200);
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function destroy(Skill $skills, Question $questions)
    {
        $question = $skills->questions->find($questions->id);
        if (!$question) {
            return response()->json(['message' => 'This question does not exist for this skill', 'code'=>404], 404);
        }

        $question->delete();
        return response()->json(['message'=>'Question has been deleted.'], 200);
    }
}
