<?php

namespace App;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Auth;
use DateTime;
use Config;

class Skill extends Model
{
    use RecordLog;
    
    protected $hidden = ['user_id', 'create_at', 'updated_at','pivot'];
    protected $fillable = ['skill', 'description', 'track_id','image', 'status_id', 'user_id', 'lesson_link', 'check'];

    // Relationships
    public function links(){ //lesson_link
        return $this->hasMany(SkillLink::class);
    }

    public function user(){
        return $this->belongsTo(User::class);
    }

    public function questions(){
        return $this->hasMany(Question::class);
    }

    public function tests() {
        return $this->belongsToMany(Test::class)->withTimestamps();
    }

    public function quizzes() {
        return $this->belongsToMany(Quiz::class)->withTimestamps();
    }

    public function status() {
        return $this->belongsTo(Status::class);
    }

    public function houses() {
        return $this->belongsToMany(House::class)->withPivot('start_date','end_date');
    }

    public function tracks() {
        return $this->belongsToMany(Track::class)->withPivot('start_date','end_date', 'skill_order')->withTimestamps();
    }
    //user's skill maxile score
    public function users(){
        return $this->belongsToMany(User::class)->withPivot('skill_test_date','skill_passed','skill_maxile','noOfTries','noOfPasses','difficulty_passed', 'noOfFails')->withTimestamps();
    }

    public function noOfQuestions(){
        return $this->questions->count();
    }

    public function noOfTries($userid){
        return $this->users()->whereUserId($userid)->select('noOfTries')->first()->noOfTries;
    }

    public function noOfPasses($userid){
        return $this->users()->whereUserId($userid)->select('noOfPasses')->first()->noOfPasses;
    }

    public function difficulty_passed($userid){
        return $this->users()->whereUserId($userid)->select('difficulty_passed')->first()->difficulty_passed;
    }

    public function skill_maxile(){
        return $this->hasOne(SkillUser::class);
    }

    public function skill_passed(){
        return $this->users()->whereUserId(Auth::user()->id)->select('skill_passed', 'skill_test_date', 'difficulty_passed');
    }

    public function handleQuiz($user, $question, $correctness){
        $userSkill= $this->users()->whereUserId($user->id)->select('noOfPasses', 'noOfTries', 'difficulty_passed','noOfFails','skill_maxile','skill_passed')->first();

        if ($userSkill) {
            $noOfTries = $userSkill->noOfTries + 1;
            $noOfPasses = $userSkill->noOfPasses;
            $noOfFails = $userSkill->noOfFails;
            $difficulty_passed = $userSkill->difficulty_passed;
            $skill_passed = $userSkill->skill_passed;
            $skill_maxile = $userSkill->skill_maxile;
        } else {
            $noOfFails = $noOfPasses = $noOfTries = $difficulty_passed =$skill_passed =0;
        }

        // mark quiz
        if ($correctness) {
            $difficulty_passed += 1;
            $noOfPasses += 1;
            $difficulty_passed = $question->difficulty_id;
        } else $noOfFails += 1;
        // check if skill passed
        $allskillquestions = count(\App\Question::whereSkillId($question->skill_id)->get());
        $correctquestions = count($user->correctQuestions()->whereSkillId($question->skill_id)->get());
        $percentage = ($correctquestions/$allskillquestions) * 100;
        $skill_passed = $percentage > 80 ? TRUE : FALSE;

        $record = [
            'skill_test_date' => new DateTime('now'),
            'skill_passed' => $skill_passed,
            'noOfPasses' =>$noOfPasses,
            'noOfFails' => $noOfFails,
            'difficulty_passed' => $difficulty_passed,
            'noOfTries'=> $noOfTries + 1];

        $this->users()->sync([$user->id=>$record]);              //update record
        return $skill_passed;
    }

    /** 
     * Determines if difficulty passed, skill passed calculate skill maxile
     * 
     */
    public function handleAnswer($userid, $difficulty, $correct, $track, $test) {
        $skill_maxile = 0;
        $userSkill= $this->users()->whereUserId($userid)->select('noOfPasses', 'noOfTries', 'difficulty_passed','noOfFails','skill_maxile','skill_passed')->first();

        if ($userSkill) {
            $noOfTries = $userSkill->noOfTries + 1;
            $noOfPasses = $userSkill->noOfPasses;
            $noOfFails = $userSkill->noOfFails;
            $difficulty_passed = $userSkill->difficulty_passed;
            $skill_passed = $userSkill->skill_passed;
            $skill_maxile = $userSkill->skill_maxile;
        } else {
            $noOfFails = $noOfPasses = $noOfTries = $difficulty_passed =$skill_passed =0;
        }

        if ($test->diagnostic){
            if ($correct){
                $skill_passed = TRUE;
                $difficulty_passed = Config::get('app.difficulty_levels');
            }
        } else { //if not diagnostic
            if (!$correct) {
                $noOfFails += 1;
                if ($difficulty <= $difficulty_passed){   // testing simpler than passed
                   if (!$test->diagnostic){
                      if ($noOfFails >= Config::get('app.number_to_fail')){
                         $difficulty_passed = max(0,$difficulty_passed - 1);
                         $noOfFails = 1;                    
                      }
                   }
                }
            } else {
                $noOfPasses += 1;
                if ($difficulty_passed < $difficulty){  //testing more difficult than passed
                    if ($test->diagnostic || $noOfPasses >= Config::get('app.number_to_pass')) {
                        $difficulty_passed = $difficulty;
                        $noOfFails = 0;
                        $noOfPasses = 1;
                    }
                }
                $skill_passed = $difficulty_passed >= Config::get('app.difficulty_levels') ? TRUE : FALSE;
            } 
            // calculate skill_maxile
            $skill_maxile = $difficulty_passed ? $skill_passed ? $track->level->end_maxile_level:$track->level->start_maxile_level+(100/Config::get('app.difficulty_levels')*$difficulty_passed) : 0; 
            if ($skill_passed) {
                $test->noOfSkillsPassed += 1;
                $test->test_maxile = max($test->test_maxile,\App\Level::find($this->tracks()->first()->level_id)->end_maxile_level);
                $test->save();
            }
        }

        $record = [
            'skill_test_date' => new DateTime('now'),
            'skill_passed' => $skill_passed,
            'difficulty_passed' => $difficulty_passed,
            'skill_maxile' => max($skill_maxile, 0),
            'noOfTries'=> $noOfTries,
            'noOfPasses'=>max(0,$noOfPasses),
            'noOfFails'=> max(0,$noOfFails)];

        $this->users()->sync([$userid=>$record]);              //update record
        return $skill_maxile;
    }

    public function forcePass($userid, $difficulty_passed, Track $track) {
        $userSkill= $this->users()->whereUserId($userid)->select('noOfPasses', 'noOfTries', 'difficulty_passed','noOfFails','skill_maxile','skill_passed')->first();

        if ($userSkill) {
            $noOfTries = $userSkill->noOfTries + 1;
            $noOfPasses = $userSkill->noOfPasses;
            $noOfFails = $userSkill->noOfFails;
            $skill_passed = $userSkill->skill_passed;
        } else {
            $noOfFails = $noOfPasses = $noOfTries = $difficulty_passed =$skill_passed =0;
        }

        $record =[
            'skill_test_date' => new DateTime('now'),
            'skill_passed' => TRUE,
            'difficulty_passed' => Config::get('app.difficulty_levels'),
            'skill_maxile' => max($userSkill->skill_maxile, $track->level->end_maxile_level),
            'noOfTries'=> $noOfTries,
            'noOfPasses'=>max(0,$noOfPasses),
            'noOfFails'=> max(0,$noOfFails)];
        return $this->users()->sync([$userid=>$record]);
    }

    public function users_failed(){
        return $this->users()->wherePivot('skill_passed','=',FALSE)->wherePivot('noOfFails','>',3)->get();
    }
}
