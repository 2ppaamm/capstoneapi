<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Question;
use Auth;
use App\Track;
use Config;

class FieldTrackQuestionController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index(Track $track)
    {
        $user = Auth::user();
        // If there are no tests
        if (count($user->incompletetests()->whereTest($user->name."'s ".$track->track." tracktest", $track->test)->get()) < 1) {
            // Create test - comment out when testing with postman
            $test = $user->tests()->create(['test'=>$user->name."'s ".$track->track." tracktest",'description'=> $user->name."'s ".date("m/d/Y")." ".$track->track." tracktest", 'start_available_time'=> date('Y-m-d', strtotime('-1 day')), 'end_available_time'=>date('Y-m-d', strtotime('+1 year')),'diagnostic'=>FALSE]);
            $skills = $track->skills()->pluck("id");

            $userId = $user->id;
            
            // find questions in track
            //$questions = Question::whereIn('skill_id', $skills)->inRandomOrder()->limit(Config::get('app.questions_per_test'))->get();

            // select questions user got correct in track
            $correctquestions = Question::whereHas('users', function ($query) use ($userId) {
                $query->where('user_id', $userId)->where('correct', 1);
            })->whereIn('skill_id', $skills)->get();

            // subtract questions that have been attempted and are correct
            $availablequestions = Question::whereNotIn('id', $correctquestions->pluck('id'))->whereIn('skill_id', $skills)->get();

            // if there are less than 20 pick already finished questions to supplement
            if (count($availablequestions) < Config::get('app.questions_per_test') - 1) {
                // get amount of needed questions
                $neededamount = (Config::get('app.questions_per_test') - 1) - count($availablequestions);

                // get needed questions
                $neededquestions = $correctquestions->shuffle()->slice(0, $neededamount);

                // add needed questions to questions
                $questions = $availablequestions->concat($neededquestions);
            } else {
                // select random questions
                $questions = $availablequestions->shuffle()->slice(0, Config::get('app.questions_per_test')-1);
            }

            return $questions;

            // put data in join table
            foreach ($questions as $question){
                $question ? $question->assigned($user, $test) : null;
            }
        } else {
            // If there are are/is an existing test(s)
            $test = $user->incompletetests()->whereTest($user->name."'s ".$track->track." tracktest", $track->test)->first();
            $new_questions = $test->uncompletedQuestions()->with('skill.tracks')->get();
            $test_questions = count($new_questions)< Config::get('app.questions_per_quiz')+1 ? $new_questions : $new_questions->take(Config::get('app.questions_per_quiz'));
            return response()->json(['message' => 'Request executed successfully', 'test'=>$test->id, 'questions'=>$test_questions, 'code'=>201]);

        }

        $fieldquestions = $test->uncompletedQuestions()->take(Config::get('app.questions_per_quiz'))->get();

        return response()->json(['message' => 'Request executed successfully', 'test'=>$test->id, 'questions'=>$fieldquestions, 'code'=>201]);
        
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
    public function store(Request $request)
    {
        //
    }

    /**
     * Display the specified resource.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function show($id)
    {
        //
    }

    /**
     * Show the form for editing the specified resource.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function edit($id)
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
    public function update(Request $request, $id)
    {
        //
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
