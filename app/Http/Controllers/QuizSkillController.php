<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use \App\Quiz;
use \App\Skill;
use \App\Question;

class QuizSkillController extends Controller
{
    public function __construct(){
    }
    
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index(Quiz $quiz)
    {
        return response() -> json (['message'=>'Quiz skills all received.','skill' => $quiz->skills()->with('links')->get(), 'code'=>200], 200);
    }

    public function list_quizzes(Skill $skill)
    {
        return response() -> json (['message'=>'Skill quizzes received.','quizzes' => $skill->quizzes, 'code'=>200], 200);
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function store(Request $request, Quiz $quiz)
    {
        foreach ($request->all() as $skill_id){
            if ($skill = Skill::find($skill_id)){
                $skill->quizzes()->sync($quiz->id, false);
            } else {
                response()->json(['message'=>'Error in skill chosen'], 401);
            } 
        }
        return response()->json(['message' => 'Skill(s) correctly added', 'skill'=>$skill, 'code'=>201]);
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function update(UpdateRequest $request, Quiz $quiz, Skill $skill)
    {
        $skill = $quiz->skills->find($skill->id);

        if (!$skill) {
            return response()->json(['message'=>'This skill is either not found or is not linked to this quiz. Cannot update','code'=>404], 404);
        }
        
        $field = $request->get('field');
        $value = $request->get('value');
            $quiz->skills()->sync([$skill->id=>[$field=>$value]]);

        try {
            $quiz->skills()->sync([$skill->id=>[$field=>$value]]);
        }
        catch(\Exception $exception){
            return response()->json(['message'=>'Update of skill in the quiz failed!','code'=> $exception->getCode()]);
        }

        return response()->json(['message'=>'Updated skill for this quiz','skill'=>$skill,'code'=>200],200);
    }

    /**
     * Display the specified resource.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function show(Quiz $quiz, Skill $skill)
    {
        if (!$quiz->skills->find($skill->id)) {
            return response()->json(['message' => 'This skill does not exist in the quiz.', 'code'=>404], 404);
        }
        return response()->json(['message'=>'Skill retrieved.','skill'=>$skill, 'code'=>200],200);
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function destroy(Quiz $quiz, Skill $skill)
    {
        $skills = $quiz->skills->find($skill->id);
        if (!$skills) {
            return response()->json(['message' => 'This skill does not exist for this quiz', 'code'=>404], 404);
        }
        $quiz->skills()->detach($skill->id);
        return response()->json(['message'=>'Skill has been removed from this quiz.', 'skills'=>$quiz->skills()->with('user')->get(), 'code'=>201], 201);
    }

    public function deleteSkills(Quiz $quiz){
        $quiz->skills()->detach();
        return response()->json(['message'=>'All skills are deleted','quiz'=>$quiz, 'code'=>201],201);
    }

    public function generateQuiz(Quiz $quiz){
        $questions = Question::whereIn('skill_id',$quiz->skills()->pluck('id'))->whereSource('diagnostic')->pluck('id');
        $quiz->questions()->sync($questions, FALSE);
        return response()->json(['message'=>'All question in the skills are generated in quiz','quiz'=>$quiz->with('questions')->get(), 'code'=>201],201);
    }

}
