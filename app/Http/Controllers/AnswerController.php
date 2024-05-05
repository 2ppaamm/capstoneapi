<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

use App\Http\Requests;
use App\Http\Requests\CreateQuizAnswersRequest;
use App\Question;
use Auth;
use App\Skill;
use DateTime;
use DB;

class AnswerController extends Controller
{
    public function __construct(){
        $currentuser =  \App\User::whereId(3)->first();
                \Auth::login($currentuser);
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
    		$skill_record =$skill->users()->whereTrackId($track->id)->whereUserId($user->id)->select('difficulty_id', 'maxile','noOfTries','noOfPasses')->first();
    		}
    	if ($skill_record){
	    	$record =['noOfTries'=> $skill_record->noOfTries + 1,
		    		  'noOfPasses' => $skill_record->noOfPasses + 1,
		    		  'track_id' => $track->id,
		    		  'skill_test_date' => new DateTime('today')];
	    	if ($skill_record->noOfPasses >= 2) {
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
    		$record = ['noOfPasses' => 1,
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
    	$skill_record = $skill->users()->whereTrackId($track->id)->whereUserId($user->id)->whereDifficultyId($difficulty->id) ? $skill->users()->whereUserId($user->id)->whereDifficultyId($difficulty->id)->select('maxile','noOfTries','noOfPasses')->first() : null;
    	if ($skill_record){
	    	$record =['noOfTries'=> $skill_record->noOfTries + 1,
		    		  'noOfFails' => $skill_record->noOfFails + 1,
		    		  'track_id' => $track->id,
		    		  'skill_test_date' => new DateTime('today'),
		    		  'noOfPasses' => 0];
	    	if ($skill_record->noOfFails >= 3) {								//difficulty failed
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
    		$record = ['noOfFails' => 1,
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
