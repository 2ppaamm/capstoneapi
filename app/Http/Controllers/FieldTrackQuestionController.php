<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Question;
use Auth;
use App\Track;
use App\User;
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
      $user = Auth::guard('sanctum')->user();
      $test = $this->getOrCreateTest($user, $track);

      $existingQuestions = $test->questions;
      $correctQuestions = $user->correctquestions()->whereIn('skill_id', $track->skills()->pluck('id'))->pluck('id');
      $allTrackQuestions = Question::whereIn('skill_id', $track->skills()->pluck('id'))->get();

      // Fill up to required number of questions
      $questionsNeeded = Config::get('app.questions_per_test') - $existingQuestions->count();
      $additional = collect();

      if ($questionsNeeded > 0) {
          $excludedIds = $existingQuestions->pluck('id')->merge($correctQuestions);
          $moreTrackQuestions = $allTrackQuestions->whereNotIn('id', $excludedIds)->take($questionsNeeded);
          $additional = $additional->merge($moreTrackQuestions);
          $questionsNeeded -= $moreTrackQuestions->count();
      }

      // Pull from same-level if still lacking
      if ($questionsNeeded > 0) {
          $sameLevelQuestions = Question::whereHas('skill.tracks', fn($q) => $q->where('level_id', $track->level_id))
              ->whereNotIn('id', $existingQuestions->pluck('id'))
              ->take($questionsNeeded)
              ->get();

          $additional = $additional->merge($sameLevelQuestions);
      }

      // Assign all new questions
      foreach ($additional as $q) {
          $q->assigned($user, $test);
      }

      // Fetch unanswered assigned questions
      $unanswered = Question::whereHas('users', fn($q) =>
          $q->where('question_user.test_id', $test->id)
            ->where('question_user.user_id', $user->id)
            ->where('question_user.question_answered', false))
          ->with('skill.tracks.level') // for Flutter
          ->get();

      $toSend = $unanswered->take(Config::get('app.questions_per_quiz'))->map(function ($q) {
          $q->skill; // make sure skill is loaded
          $track = $q->skill->tracks()->first(); // assumes 1 main track
          $q->level = $track?->level?->name ?? ''; // attach level name
          return $q;
      });

      $doneNess = $user->tracks()->where('tracks.id', $track->id)->first()->pivot->doneNess ?? 0;

      return response()->json([
          'message' => 'Request executed successfully',
          'test' => $test->id,
          'questions' => $toSend,
          'track_doneness' => $doneNess,
          'code' => 201
      ]);
  }

  private function getOrCreateTest($user, $track)
  {
      $test = $user->incompletetests()
          ->where('test', 'LIKE', "%{$track->track} tracktest%")
          ->latest()
          ->first();

      if (!$test || !$test->uncompletedQuestions()->count()) {
          return $user->tests()->create([
              'test' => "{$user->name}'s {$track->track} tracktest",
              'description' => "{$user->name}'s " . now()->format('m/d/Y') . " {$track->track} tracktest",
              'level_id' => $track->level_id,
              'start_available_time' => now()->subDay(),
              'end_available_time' => now()->addYear(),
              'diagnostic' => false
          ]);
      }

      return $test;
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
