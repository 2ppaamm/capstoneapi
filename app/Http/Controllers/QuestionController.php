<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Gate;
use App\Http\Requests;
use App\Http\Controllers\Controller;
use App\Question;
use App\Skill;
use App\Track;
use App\Http\Requests\CreateQuestionRequest;
use App\Http\Requests\UpdateRequest;

class QuestionController extends Controller
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
        $questions = Question::with('solutions','author','difficulty', 'skill.tracks.level','skill.tracks.field','type','status')->paginate(20);
//        $questions = Cache::remember('questions', 15/60, function(){
  //          return Question::with('solutions','author','difficulty', 'skill.tracks.level','skill.tracks.field','type','status')->paginate(20);
        //});
//        return $questions->items();
        return response()->json(['next'=>$questions->nextPageUrl(), 'previous'=>$questions->previousPageUrl(),'num_pages'=>$questions->lastPage(),'questions'=>$questions->items()], 200);
    }

    public function search_init(){
        return response()->json(['levels'=>\App\Level::select('id','level','description')->get(), 'skills'=>\App\Skill::select('id','skill','description')->get(),'code'=>201],201);
    }
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function search(Request $request)
    {
        $questions = null;
        if ($request->skill){
            $questions = Cache::remember('questions', 15/60, function() use ($request) {
            return Question::whereSkillId($request->skill)->with('solutions','author','difficulty', 'skill.tracks.level','skill.tracks.field','type','status')->paginate(20);
                });

        }
        if ($request->level){
            $questions = Cache::remember('questions',15/60, function() use ($request){
            return Question::with('solutions','author','difficulty', 'skill.tracks.level','skill.tracks.field','type','status')->whereIn('skill_id', Skill::whereHas('tracks', function ($query) use ($request) {
                       $query->whereIn('id', \App\Level::find($request->level)->tracks()->pluck('id')->toArray());
                        })->pluck('id')->toArray())->paginate(20);

            });
        }
        if ($request->keyword){
            $questions = Cache::remember('questions',15/60, function() use ($request){
            return Question::with('solutions','author','difficulty', 'skill.tracks.level','skill.tracks.field','type','status')->where('question','LIKE','%'.$request->keyword.'%')->paginate(20);
            });
        }
        return response()->json(['next'=>$questions->nextPageUrl(), 'previous'=>$questions->previousPageUrl(),'num_pages'=>$questions->lastPage(),'questions'=>$questions->items()], 200);
    }


    public function create(){
        $levels=\App\Level::with(['tracks.skills'=>function($query){
                $query->select('id', 'skill','description');
                }])->select('id','level','description')->get();
        return response()->json(['statuses'=>\App\Status::select('id','status','description')->get(), 'difficulties'=>\App\Difficulty::select('id','difficulty','description')->get(),'type'=>\App\Type::select('id','type','description')->get(),'skills'=>$levels,'code'=>201],201);
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
        $question = $request->except(['question_image','answer0_image','answer1_image','answer2_image','answer3_image']);
        $question['user_id'] = $user->id;
        $image_path = '/images/questions/question_image';

        if ($request->type_id == 2) {
            $question['answer0'] = $request->answer0 == 'null'? NULL: (int)$request->answer0;
            $question['answer1'] = $request->answer1 == 'null'? NULL: (int)$request->answer1;
            $question['answer2'] = $request->answer2 == 'null'? NULL: (int)$request->answer2;
            $question['answer3'] = $request->answer3 == 'null'? NULL: (int)$request->answer3;
        }

        if ($request->hasFile('question_image')) {
            $q_image='q'.time().'.png';
            $file = $request->question_image->move(public_path().$image_path, $q_image);
            $question['question_image'] = '/images/questions/question_image/'.$q_image;
        } 

        if ($request->hasFile('answer0_image')) {
            $a0_image='a0'.time().'.png';
            $file = $request->answer0_image->move(public_path('images/questions/answers'), $a0_image);            
            $question['answer0_image'] = '/images/questions/answers/'.$a0_image;
        }

        if ($request->hasFile('answer1_image')) {
            $a1_image='a1'.time().'.png';
            $file = $request->answer1_image->move(public_path('images/questions/answers'), $a1_image);            
            $question['answer1_image'] = '/images/questions/answers/'.$a1_image;
        }
        if ($request->hasFile('answer2_image')) {
            $a2_image='a2'.time().'.png';
            $file = $request->answer2_image->move(public_path('images/questions/answers'), $a2_image);            
            $question['answer2_image'] = '/images/questions/answers/'.$a2_image;
        }
        if ($request->hasFile('answer3_image')) {
            $a3_image='a3'.time().'.png';
            $file = $request->answer3_image->move(public_path('images/questions/answers'), $a3_image);            
            $question['answer3_image'] = '/images/questions/answers/'.$a3_image;
        }
        $question = Question::create($question);
        return response()->json(['message' => 'Question correctly added', 'question'=>$question, 'code'=>201]);
    }

    /**
     * Display the specified resource.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function show(Question $question)
    {
        return response()->json(['question' => $question, 'code'=>201], 201);
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function update(Request $request, Question $question)
    {
//        if(Gate::denies('modify_question', $question)){
//            return response()->json(['message'=> 'Access denied. You are not authorized to modify this question.', 'code'=>403],403);
//        }
        $user = Auth::user();
        if ($question->user_id != $user->id && !$user->is_admin) {
            return response()->json(['message'=> 'Access denied. You are not authorized to modify this question.', 'code'=>403],403);
        }

        if ($request->hasFile('question_image')) {
            if (file_exists($question->question_image)) unlink($question->question_image);
            $q_image = 'q'.time();
            $question->question_image = '/images/questions/question_image/'.$q_image.'.png';

            $file = $request->question_image->move(public_path('images/questions/question_image'), $q_image.'.png');
        } 

        if ($request->hasFile('answer0_image')) {
            if (file_exists($question->answer0_image)) unlink($question->answer0_image);
            $a0_image = 'a0'.time();
            $question['answer0_image'] = '/images/questions/answers/'.$a0_image.'.png';

            $file = $request->answer0_image->move(public_path('images/questions/answers'), $a0_image.'.png');
        } 

        if ($request->hasFile('answer1_image')) {
            if (file_exists($question->answer1_image)) unlink($question->answer1_image);
            $a1_image = 'a1'.time();
            $question['answer1_image'] = '/images/questions/answers/'.$a1_image.'.png';

            $file = $request->answer1_image->move(public_path('images/questions/answers'), $a1_image.'.png');
        } 

        if ($request->hasFile('answer2_image')) {
            if (file_exists($question->answer2_image)) unlink($question->answer2_image);
            $a2_image = 'a2'.time();
            $question['answer2_image'] = '/images/questions/answers/'.$a2_image.'.png';

            $file = $request->answer2_image->move(public_path('images/questions/answers'), $a2_image.'.png');
        } 

        if ($request->hasFile('answer3_image')) {
            if (file_exists($question->answer3_image)) unlink($question->answer3_image);
            $a3_image = 'a3'.time();
            $question['answer3_image'] = '/images/questions/answers/'.$a3_image.'.png';

            $file = $request->answer3_image->move(public_path('images/questions/answers'), $a3_image.'.png');
        } 

        if ($question->type_id == 2) {
            $question['answer0'] = $request->answer0 == 'null'? NULL: intval($request->answer0);
            $question['answer1'] = !$request->answer1 || $request->answer1 == 'null'? NULL: (int)$request->answer1;
            $question['answer2'] = !$request->answer2 || $request->answer2 == 'null'? NULL: (int)$request->answer2;
            $question['answer3'] = !$request->answer3 || $request->answer3 == 'null'? NULL: (int)$request->answer3;
        }

        $question->fill($request->except(['question_image','answer0_image','answer1_image','answer2_image','answer3_image']))->save();

        return response()->json(['message'=>'Question has been updated','question' => $question, 'code'=>200], 200);
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function destroy(Question $question)
    {
       if (sizeof($question->users)>0){
            return response()->json(['message'=>'This question has been answered by some users on the system. You cannot delete it.','code'=>500],500);
        }
        $question->delete();
        return response()->json(['message'=>'Question has been deleted.'], 200);
    }
}
