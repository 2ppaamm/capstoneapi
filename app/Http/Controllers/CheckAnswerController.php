<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

use App\Http\Requests;
use Auth;
use App\Question;
use App\Http\Requests\CreateQuizAnswersRequest;
use DateTime;
use App\User;

class CheckAnswerController extends Controller
{
    public function __construct(){
         Auth::login(User::find(1));

    }

    /**
     * Sends a list of questions of the test number to the front end
     *
     * One question from the highest skill of each track from the appropriate level
     *
     * @return \Illuminate\Http\Response
     */
    public function index(){
       	$user = User::find(1);
      	$questions = null;
    	$test = count($user->currenttest)<1 ?  $user->tests()->create(['test'=>$user->name."'s QA test",'description'=> $user->name."'s QA test", 'diagnostic'=>FALSE]) : $user->currenttest[0];

        $questions = count($test->questions) < 1 ? Question::where('id','>', 299)->get():0;
	    if ($questions) {
            foreach($questions as $question) {
                $question->assigned($user, $test);
                $track = $question->skill->tracks->first();
                $track->users()->sync([$user->id], false);
    	    }
        }
        $questions = $test->uncompletedQuestions;        
        $test_questions = count($questions)< 6 ? $questions : $questions->take(5);
        return response()->json(['message' => 'Request executed successfully', 'test'=>$test->id, 'questions'=>$test_questions, 'code'=>201]);
//         return $test->fieldQuestions($user);
    }

    public function answer(CreateQuizAnswersRequest $request){
    	$user = User::find(1);
        $old_maxile = $user->maxile_level;
    	$test = \App\Test::find($request->test);
    	if (!$test){
	    	return response()->json(['message' => 'Invalid Test Number', 'code'=>405], 405);   	
    	}

        foreach ($request->question_id as $key=>$question_id) {
            $answered = FALSE;
            $correctness = FALSE;
            $question = Question::find($question_id);
            if (!$question){
                $user->errorlogs()->create(['error'=>'Question ',$question_id.' not found']);
                return response()->json(['message'=>'Error in question. No such question', 'code'=>403]);                
            }
            $assigned = $question->users()->whereUserId($user->id)->first();
            if (!$assigned) {
                $user->errorlogs()->create(['error'=>'Question ',$question_id.' not assigned to '. $user->name]);
                return response()->json(['message'=>'Question ',$question_id.' not assigned to '. $user->name, 'code'=>403]);                                
            }
            if ($question->type_id == 2) {
                $answers = isset($request->answer[$key]) ? $request->answer[$key] : null;
//                return count($answers);
                $correct3 = sizeof($answers) > 3 ? $answers[3] == $question->answer3 ? TRUE : FALSE : TRUE;
                $correct2 = sizeof($answers) > 2 ? $answers[2] == $question->answer2 ? TRUE : FALSE : TRUE;
                $correct1 = sizeof($answers) > 1 ? $answers[1] == $question->answer1 ? TRUE : FALSE : TRUE;
                $correct = sizeof($answers) > 0 ? $answers[0] == $question->answer0 ? TRUE : FALSE : TRUE;
                $correctness = $correct + $correct1 + $correct2 + $correct3 > 3? TRUE: FALSE;
            } else $correctness = $question->correct_answer != $request->answer[$key] ? FALSE:TRUE;
            $answered = $question->answered($user, $correctness, $test);
            $track = $question->skill->tracks->intersect($user->testedTracks)->first();
            $skill_maxile = $question->skill->handleAnswer($user->id, $question->difficulty_id, $correctness, $track, $test->diagnostic);
            $track_maxile = $track->calculateMaxile($user, $test->diagnostic);
 //           $user_maxile = $user->calculateUserMaxile($test);             
		}

        $questions = $test->uncompletedQuestions;        
        $test_questions = count($questions)< 6 ? $questions : $questions->take(5);
        return response()->json(['message' => 'Request executed successfully', 'test'=>$test->id, 'questions'=>$test_questions, 'code'=>201]);
    }
}