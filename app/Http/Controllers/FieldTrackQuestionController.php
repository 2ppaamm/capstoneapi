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
        //to work with React
        $user = Auth::user();
        $userId = $user->id;
        $trackId = $track->id;
        $questions = collect([]);
        $fieldQuestions=[];
        $num_to_field=Config::get('app.questions_per_quiz');
        $questions_per_test=Config::get('app.questions_per_test') - 1;
        $additionalQuestions=[];
        $levelId = $track->level->id;

        // 1. Find a list of all questions in the track.
        $allQuestions = Question::whereIn('skill_id',  $track->skills()->pluck('id'))->get();
        // 2. Find all the questions in the track that user has gotten correct=TRUE in the question_user pivot.
        $correctQuestions = $user->correctquestions()->whereIn('skill_id',  $track->skills()->pluck('id'))->get();

        // 3. Handling new/incomplete test creation or selection
        $test = $user->incompletetests()->where('test', 'LIKE', '%' . $track->track . ' tracktest%')->latest()->first();

        if (!$test || !(count($test->uncompletedQuestions))) {
            // Create a new test if there's no existing incomplete track test
            $test = $user->tests()->create([
                'test' => $user->name."'s ".$track->track." tracktest",
                'description'=> $user->name."'s ".date("m/d/Y")." ".$track->track." tracktest",
                'level_id' => $track->level_id,
                'start_available_time' => date('Y-m-d', strtotime('-1 day')),
                'end_available_time' => date('Y-m-d', strtotime('+1 year')),
                'diagnostic' => false
            ]);

            // Find Config::get('app.questions_per_test') questions from (1) minus (2)
            $additionalQuestions = $allQuestions->whereNotIn('id', $correctQuestions->pluck('id'))->take($questions_per_test);

        } else {
    // If there's an existing incomplete test, use the latest one
            $questions = $test->questions;
        // Supplement questions to meet the required count if necessary
            if ($questions->count() < $questions_per_test) {
                $questionsNeeded = $questions_per_test - $questions->count();
                // Make sure to close the parenthesis correctly in the min() function call
                $additionalQuestionsCount = min($questionsNeeded, $allQuestions->count());

                // Assuming $questions is a collection and can be directly used to filter $allQuestions
                // Correctly use merge() on a Collection instance and make sure $allQuestions is a Collection of all available questions
                $additionalQuestions = $allQuestions->whereNotIn('id', $questions->pluck('id')->merge($correctQuestions->pluck('id')))->take($additionalQuestionsCount);
            }
        }

        $questions = $questions->merge($additionalQuestions); // Update the questions collection to include the new additions

        $levelQuestionsRequired = $questions_per_test - count($questions);

             // Fetch additional questions from the same level, excluding already selected questions
        $levelQuestions = $levelQuestionsRequired > 0? Question::whereHas('skill.tracks', function ($query) use ($levelId) {
            $query->where('level_id', $levelId);
        })
        ->whereNotIn('id', $questions->pluck('id')) // Assuming $questions is a collection
        ->take($levelQuestionsRequired)
        ->get() : collect([]);

        $additionalQuestions = collect($additionalQuestions)->merge($levelQuestions); // Update the questions collection to include the new level additions      
        foreach ($additionalQuestions as $question) {
            $question->assigned($user, $test);
        }

 // 6. Find the doneNess from the user_track pivot table

        $doneNess = $user->tracks()->where('tracks.id', $trackId)->first()->pivot->doneNess ?? 0;
        
        $testId = $test->id;

        $unansweredQuestions = Question::whereHas('users', function ($query) use ($userId, $testId) {
            $query->where('question_user.test_id', $testId)
                  ->where('question_user.user_id', $userId)
                  ->where('question_user.question_answered', false);
        })
        ->with('skill')
        ->get();

        $fieldQuestions=(count($unansweredQuestions))> $num_to_field ? $unansweredQuestions->take($num_to_field) : $unansweredQuestions;

        // Send response
        return response()->json([
            'message' => 'Request executed successfully',
            'test' => $test->id,
            'questions' => $fieldQuestions,
            'track_doneness' => $doneNess,
            'code' => 201
        ]);
    }


 /*   public function index(Track $track)
    {
        $user = Auth::user();
        $questions=[];
        // If there are no tests

        if ($user->incompletetests()
         ->where('test', 'LIKE', '%' . $track->track . ' tracktest%')
         ->count() < 1) {
            // Create test - comment out when testing with postman
            $test = $user->tests()->create(['test'=>$user->name."'s ".$track->track." tracktest",'description'=> $user->name."'s ".date("m/d/Y")." ".$track->track." tracktest", 'level_id'=>$track->level_id,'start_available_time'=> date('Y-m-d', strtotime('-1 day')), 'end_available_time'=>date('Y-m-d', strtotime('+1 year')),'diagnostic'=>FALSE]);
            $skills = $track->skills()->pluck("id");

            $userId = $user->id;
            
            // find questions in track
            $questions = Question::whereIn('skill_id', $skills)->inRandomOrder()->limit(Config::get('app.questions_per_test'))->get();

            // select questions user got correct in track
            $correctquestions = Question::whereHas('users', function ($query) use ($userId) {
                $query->where('user_id', $userId)->where('correct', 1);
            })->whereIn('skill_id', $skills)->get();

            // subtract questions that have been attempted and are correct
            $unattemptedquestions = Question::whereNotIn('id', $correctquestions->pluck('id'))->whereIn('skill_id', $skills)->get();

            // if there are less than 20 pick already finished questions to supplement
            if (count($test->questions) < Config::get('app.questions_per_test') - 1) {
                // get amount of needed questions
                $neededamount = (Config::get('app.questions_per_test') - 1) - count($test->questions);

                // get needed questions
                $neededquestions = $correctquestions->shuffle()->slice(0, $neededamount);

                 // put new needed questions in join table
                foreach ($questions as $question){
                  $question ? $question->assigned($user, $test) : null;
                }
                // add needed questions to questions
                $questions = $unattemptedquestions->concat($neededquestions);

            }              
        } else {
            // If there are are/is an existing test(s)
          $test = $user->incompletetests()
         ->where('test', 'REGEXP', '[[:<:]]' . preg_quote($track->track) . '[[:>:]]')->latest()->first();
            $testquestions=$test->questions;
            // if there are less than 20 pick any question that are not in $testquestions
            if (count($testquestions) < Config::get('app.questions_per_test') - 1) {
                // get amount of needed questions
                $neededamount = (Config::get('app.questions_per_test') - 1) - count($testquestions);
 
                // get needed questions
                $neededquestions = $track->questions()
                ->whereNotIn('id', $testquestions->pluck('id'))
                ->get()
                ->shuffle()
                ->take($neededamount);

                // put data in join table
                foreach ($neededquestions as $question){
                    $question ? $question->assigned($user, $test) : null;
                }
                // add needed questions to questions
                $questions = $testquestions->concat($neededquestions);
            } 
        }
        $fieldquestions = $test->uncompletedQuestions()->take(Config::get('app.questions_per_quiz'))->get();

        return response()->json(['message' => 'Request executed successfully', 'test'=>$test->id, 'questions'=>$fieldquestions, 'code'=>201]);
        
    }
*/
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
