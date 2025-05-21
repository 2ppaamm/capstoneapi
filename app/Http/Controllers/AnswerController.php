<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

use App\Http\Requests;
use App\Http\Requests\CreateQuizAnswersRequest;
use App\Question;
use App\UserLives;
use App\QuestionUser;
use App\Plan;
use App\Test;
use App\TestUser;
use App\Level;
use Auth;
use App\Skill;
use DateTime;
use DB;
use Illuminate\Support\Facades\Log;

class AnswerController extends Controller
{
    protected $user;

    public function __construct()
    {
        $this->middleware(function ($request, $next) {
            $this->user = Auth::guard('sanctum')->user();
            return $next($request);
        });
    }

    public function answer(CreateQuizAnswersRequest $request)
    {
        $user = Auth::guard('sanctum')->user();
        $currentLives = $user->lives()->first();
        $testId = $request->input('test');
        $test = Test::find($testId);
        $submittedAnswers = $request->input('answer');
        $submittedIds = $request->input('question_id');

        // Step 1: Validate test ownership
         $pivot = TestUser::where('test_id', $testId)
            ->where('user_id', $user->id)
            ->first();

        if (!$pivot) {
            return response()->json(['code' => 403, 'message' => 'Test not assigned to this user.']);
        }

        // Step 2: Early exit if test already completed
        if ($pivot->test_completed || $test->completed) {
            return response()->json([
                'code' => 206,
                'kudos' => $test->kudos_earned,
                'maxile' => (float) $user->maxile_level,
                'completed' => true,
                'percentage' => (float) $test->test_score, 
                    'name' => $user->firstname,
                'message' => 'Test previously completed.',
                'lives' => $user->lives,
            ]);
        }
        // Step 3: Fetch user plan and lives info
        if (!$user->hasLivesRemaining()) {
            return response()->json([
                'message' => 'No lives remaining. Please wait or upgrade.',
                'code' => 403
            ]);
        }

        // Step 4: Process submitted answers in transaction
        DB::beginTransaction();

        try {
            $kudosEarned = 0;
            $livesLost = 0;

            foreach ($submittedIds as $questionId) {

                $userAnswers = $submittedAnswers[$questionId] ?? null;

                if ($userAnswers === null) {
                    continue; // skip if not found
                }

                $questionUser = QuestionUser::where('test_id', $testId)
                    ->where('question_id', $questionId)
                    ->where('user_id', $user->id)
                    ->first();

                if (!$questionUser) {
                    DB::rollBack();
                    return response()->json([
                        'code' => 403,
                        'message' => "Question $questionId not assigned to this user."
                    ], 403);
                }

                $question = $questionUser->question;

                $correct = $question->correctness($user, $userAnswers);

                $kudosEarned += $correct ? ($question->difficulty_id + 1) : 1;

                $question->answered($user, $correct, $test);

             // Handle Lives
                if (!$correct) {
                    $user->livesTransactions()->create([
                        'amount' => -1,
                        'reason' => 'incorrect_answer',
                        'created_at' => now('UTC'),
                        'updated_at' => now('UTC')
                    ]);

                $currentLives = $user->livesTransactions()->sum('amount');

                }

                $question->processProgressFor($user, $correct, $test);
            }
            // Step 5: Update test and user stats
 
            $test->questions_answered += count($submittedAnswers);
 
            $test->kudos_earned += $kudosEarned;

            // Step 6: Check if test should be completed
            $unansweredCount = QuestionUser::where('test_id', $test->id)
                ->where('user_id', $user->id)
                ->whereNull('answered_date')
                ->count();

            if ($unansweredCount === 0) {
                $test->completed = TRUE;

                $totalQuestions = QuestionUser::where('test_id', $test->id)
                    ->where('user_id', $user->id)
                    ->count();

                $correctAnswers = QuestionUser::where('test_id', $test->id)
                    ->where('user_id', $user->id)
                    ->where('correct', true)
                    ->count();
                $score = $totalQuestions > 0
                    ? round(($correctAnswers / $totalQuestions) * 100, 2)
                    : 0;

                $test->test_score = $score;
                $test->save();
                $user->save();
                $test->testee()->updateExistingPivot($user->id, [ 
                    'test_completed' => true,
                    'completed_date' => now(),
                    'result' => $score,
                    'kudos' => $test->kudos_earned ?? $test->kudos // fallback
                    ]);
                $level = \App\Level::where('start_maxile_level', '<=', $user->maxile_level)
                    ->where('end_maxile_level', '>', $user->maxile_level)
                    ->first();

                $encouragements = $level && $level->encouragements
                    ? explode('|', $level->encouragements)
                    : ['Keep going!', 'Good effort!'];

                $encouragement = $encouragements[array_rand($encouragements)];

                DB::commit();
                return response()->json([
                    'code' => 206,
                    'encouragement' => $encouragement,
                    'kudos' => $test->kudos_earned,
                    'maxile' => (float) $user->maxile_level,
                    'completed' => true,
                    'percentage' => $test->test_score, 
                    'name' => $user->firstname,
                    'lives'=> $user->lives,
                ]);
            }

            DB::commit();

            return $test->buildResponseFor($user);

        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Answer processing failed: ' . $e->getMessage());
            return response()->json(['message' => 'Internal error', 'code' => 500]);
        }
    }

    public function checkQuiz(CreateQuizAnswersRequest $request){
    	$user = Auth::user();
        //define a new quiz for user
        $quiz = $user->quizzes()->create(['description'=> $user->name."'s quiz"]);
    	$message = [];
    	foreach ($request->question_id as $key=>$question_id) {
    		$question = Question::find($question_id);
            //save all questions into the quiz
            $question->quizzes()->attach($quiz);
    		$question_record = $user->myQuestions()->where('question_id',$question_id)->select('attempts')->first(); 
    		if ($question AND $question_record){
    			$record['question_answered'] = TRUE;
    			$record['answered_date'] = new DateTime('today');
    			$record['attempts'] = $question_record->attempts + 1;
    			if ($question->correct_answer == $request->answer[$key]) {
    				$new_question = $this->correct($question, $question->skill);
    				$record['correct'] = TRUE;
    				array_push($message, $question_id. ' Correct. New Question is '. $new_question->id);
    			} else {
	    		 	$new_question = $this->wrong($question, $question->skill);
	    		 	$record['correct'] = FALSE;
    				array_push($message, $question_id. ' Incorrect. New Question is '. $new_question->id);
    			}
		    	$user->unansweredQuestions()->sync([$question_id=>$record]);    	
	    	    $new_question ? $user->myQuestions()->sync([$new_question->id],false) : array_push($message, 'No new question has been included.');
    	   } else array_push($message, $question_id.' Question not found');
    	}
    	return response()->json(['message' => $message, 'unanswered_questions'=>$user->unansweredQuestions,'code'=>201]);
    }


    public function correct(Question $question, Skill $skill){
    	$user = Auth::user();
        $user->maxile_level += 0.5;
        $user->save();
    	return Question::find(rand(1,18)*2);
/*    	$user = Auth::user();
    	$question = new Question;
    	$level = $skill->tracks->first()->level;
    	$track = $skill->tracks->intersect($user->tracks)->first();
    	$max_skill = $track->maxSkill->first()->max_skill_order;
    	return $difficulty = $question->difficulty;
    	$noOfDifficulties = count(\App\Difficulty::all());
    	$maxilepTrack = 100/count($level->tracks);
    	$maxilepSkill = $maxilepTrack/count($track->skills);
    	$maxilepDifficulties = $maxilepSkill/$noOfDifficulties;
    	if ($skill->users()->whereTrackId($track->id)->whereUserId($user->id)) { 
    		$skill_record =$skill->users()->whereTrackId($track->id)->whereUserId($user->id)->select('difficulty_id', 'maxile','noOfTries','pass_streak')->first();
    		}
    	if ($skill_record){
	    	$record =['noOfTries'=> $skill_record->noOfTries + 1,
		    		  'pass_streak' => $skill_record->pass_streak + 1,
		    		  'track_id' => $track->id,
		    		  'skill_test_date' => new DateTime('today')];
	    	if ($skill_record->pass_streak >= 2) {
	    		//difficulty passed
	    		if ($difficulty->id == $noOfDifficulties) { 	
	    		//skill passed
	    			if ($track->skill_order>=$max_skill || count($track->skills->intersect($user->completedSkills)) == count($track->skills)){								
	    				//track passed: log, then find a new track, new skill and new question
	    				$user->tracks()->save($track,['track_maxile'=>$maxilepTrack, 'track_passed'=>TRUE, 'track_test_date'=> new DateTime('today')]);
	    				$new_track = $user->enrolledClasses->first()->tracks->diff($user->passedTracks)->first();
	    				$new_skill = $new_track->skills->diff($user->completedSkills)->first();
	    				$question = $new_skill->questions()->whereDifficultyId(1)->first();
	    			} else {
	    				// skill passed, track not passed: find new skill, then question
			    		$record['skill_passed'] = TRUE;
			    		$record['maxile'] = $maxilepSkill;
		    			$new_skill = $track->skills()->where('skill_order','>',$track->skill_order)->first();
	    				$question = Question::whereSkillId($new_skill->id)->whereDifficultyId(1)->first();
	    			}
	    		} else{
	    			// difficulty passed, skill and track not passed.
		    		$record['maxile'] = min($skill_record->maxile + $maxilepDifficulties, $maxilepSkill);
		    		$record['difficulty_passed'] = TRUE;
	    			$record['skill_passed'] = FALSE;
	    			$question = Question::whereSkillId($skill_record->skill_id)->whereDifficultyId($skill_record->difficulty_id + 1)->first();
	    		}
	    	} else {
    			$record['skill_passed'] = FALSE;
    			$record['difficulty_passed'] = FALSE;
    			$record['maxile'] = $skill_record->maxile;
    			$question = Question::whereSkillId($skill_record->skill_id)->whereDifficultyId($skill_record->difficulty_id)->first();
	    	}
		    $skill->users()->updateExistingPivot(Auth::user()->id, $record);  // update current log
		}
    	else{ 
    		$record = ['pass_streak' => 1,
    				   'difficulty_id' =>$difficulty->id,
  		    		   'track_id' => $track->id,
     				   'noOfTries' =>1];
		    $skill->users()->save($user, $record);					//update current log
    		$question = Question::whereSkillId($skill->id)->whereDifficultyId($difficulty->id)->where('id','!=', $question)->orderBy(DB::raw('RAND()'))->take(1)->get();
		}
		return $question;
*/    }

    public function wrong(Question $question, Skill $skill){
    	return Question::find((rand(1,18)*2)-1);
/*    	$user = Auth::user();
    	$level = $skill->tracks->first()->level;
    	$track = $skill->tracks->intersect($user->tracks)->first();
    	$difficulty = $question->difficulty;
    	$noOfDifficulties = count(\App\Difficulty::all());
    	$maxilepTrack = 100/count($level->tracks);
    	$maxilepSkill = $maxilepTrack/count($track->skills);
    	$maxilepDifficulties = $maxilepSkill/$noOfDifficulties;
    	$skill_record = $skill->users()->whereTrackId($track->id)->whereUserId($user->id)->whereDifficultyId($difficulty->id) ? $skill->users()->whereUserId($user->id)->whereDifficultyId($difficulty->id)->select('maxile','noOfTries','pass_streak')->first() : null;
    	if ($skill_record){
	    	$record =['noOfTries'=> $skill_record->noOfTries + 1,
		    		  'fail_streak' => $skill_record->fail_streak + 1,
		    		  'track_id' => $track->id,
		    		  'skill_test_date' => new DateTime('today'),
		    		  'pass_streak' => 0];
	    	if ($skill_record->fail_streak >= 3) {								//difficulty failed
	    		if ($skill_record->difficulty_passed){
	    			$record['maxile'] = max($skill_record->maxile - $maxilepDifficulties, 0);
	    		}
	    		if ($difficulty->id == 1) { 	
	    		//lowest difficulty failed, move to lower skill
	    			$new_skill = $track->skills->intersect(Auth::user()->completedSkills)->last();
	    			if (count($track->skills->intersect(Auth::user()->completedSkills)) == 0){				$question = Question::findOrFail(1); //placeholder for now
	    			} else {
	    				$question = Question::whereSkillId($new_skill->id)->whereDifficultyId($noOfDifficulties)->first();
	    			}
	    		} else{
	    			$question = Question::whereSkillId($skill_record->skill_id)->whereDifficultyId($skill_record->difficulty_id - 1)->first();
	    		}
	    	} else {
    			$question = Question::whereSkillId($skill_record->skill_id)->whereDifficultyId($skill_record->difficulty_id)->first();
	    	}
		    $skill->users()->updateExistingPivot($user->id, $record);  // update current log
		}
    	else{ 
    		$record = ['fail_streak' => 1,
    				   'difficulty_id' =>$difficulty->id,
  		    		   'track_id' => $track->id,
     				   'noOfTries' =>1,
    				   'maxile' => 0,
					   'skill_test_date' => new DateTime('today')];
		    $skill->users()->save($user, $record);					//update current log
    		$question = Question::whereSkillId($skill_record->skill_id)->whereDifficultyId($skill_record->difficulty_id)->firstOrFail();
		}
		return $question;
*/    }
}
