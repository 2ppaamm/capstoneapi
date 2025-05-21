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
        return $this->belongsToMany(User::class)->withPivot('skill_test_date','skill_passed','skill_maxile','noOfTries','correct_streak','difficulty_passed', 'fail_streak')->withTimestamps();
    }

    public function videos(){
        return $this->hasMany(Video::class);
    }


    public function noOfQuestions(){
        return $this->questions->count();
    }

    public function noOfTries($userid){
        return $this->users()->whereUserId($userid)->select('noOfTries')->first()->noOfTries;
    }

    public function correct_streak($userid){
        return $this->users()->whereUserId($userid)->select('correct_streak')->first()->correct_streak;
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
        $userSkill= $this->users()->whereUserId($user->id)->select('correct_streak', 'noOfTries', 'difficulty_passed','fail_streak','skill_maxile','skill_passed')->first();

        if ($userSkill) {
            $noOfTries = $userSkill->noOfTries + 1;
            $correct_streak = $userSkill->correct_streak;
            $fail_streak = $userSkill->fail_streak;
            $difficulty_passed = $userSkill->difficulty_passed;
            $skill_passed = $userSkill->skill_passed;
            $skill_maxile = $userSkill->skill_maxile;
        } else {
            $fail_streak = $correct_streak = $noOfTries = $difficulty_passed =$skill_passed =0;
        }

        // mark quiz
        if ($correctness) {
            $difficulty_passed += 1;
            $correct_streak += 1;
            $difficulty_passed = $question->difficulty_id;
        } else $fail_streak += 1;
        // check if skill passed
        $allskillquestions = count(\App\Question::whereSkillId($question->skill_id)->get());
        $correctquestions = count($user->correctQuestions()->whereSkillId($question->skill_id)->get());
        $percentage = ($correctquestions/$allskillquestions) * 100;
        $skill_passed = $percentage > 80 ? TRUE : FALSE;

        $record = [
            'skill_test_date' => new DateTime('now'),
            'skill_passed' => $skill_passed,
            'correct_streak' =>$correct_streak,
            'fail_streak' => $fail_streak,
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
        $userSkill= $this->users()->whereUserId($userid)->select('correct_streak', 'noOfTries', 'difficulty_passed','fail_streak','skill_maxile','skill_passed')->first();

        if ($userSkill) {
            $noOfTries = $userSkill->noOfTries + 1;
            $correct_streak = $userSkill->correct_streak;
            $fail_streak = $userSkill->fail_streak;
            $difficulty_passed = $userSkill->difficulty_passed;
            $skill_passed = $userSkill->skill_passed;
            $skill_maxile = $userSkill->skill_maxile;
        } else {
            $fail_streak = $correct_streak = $noOfTries = $difficulty_passed =$skill_passed =0;
        }

        if ($test->diagnostic){
            if ($correct){
                $skill_passed = TRUE;
                $difficulty_passed = Config::get('app.difficulty_levels');
            }
        } else { //if not diagnostic
            if (!$correct) {
                $fail_streak += 1;
                if ($difficulty <= $difficulty_passed){   // testing simpler than passed
                   if (!$test->diagnostic){
                      if ($fail_streak >= Config::get('app.number_to_fail')){
                         $difficulty_passed = max(0,$difficulty_passed - 1);
                         $fail_streak = 1;                    
                      }
                   }
                }
            } else {
                $correct_streak += 1;
                if ($difficulty_passed < $difficulty){  //testing more difficult than passed
                    if ($test->diagnostic || $correct_streak >= Config::get('app.number_to_pass')) {
                        $difficulty_passed = $difficulty;
                        $fail_streak = 0;
                        $correct_streak = 1;
                    }
                }
                $skill_passed = $difficulty_passed >= Config::get('app.difficulty_levels') ? TRUE : FALSE;
            } 
            // calculate skill_maxile
            $skill_maxile = $difficulty_passed ? $skill_passed ? $track->level->end_maxile_level:$track->level->start_maxile_level+(100/Config::get('app.difficulty_levels')*$difficulty_passed) : 0; 
            if ($skill_passed) {
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
            'correct_streak'=>max(0,$correct_streak),
            'fail_streak'=> max(0,$fail_streak)];

        $this->users()->sync([$userid=>$record]);              //update record
        return $skill_maxile;
    }

    public function forcePass($userid, $difficulty_passed, Track $track) {
        $userSkill= $this->users()->whereUserId($userid)->select('correct_streak', 'noOfTries', 'difficulty_passed','fail_streak','skill_maxile','skill_passed')->first();

        if ($userSkill) {
            $noOfTries = $userSkill->noOfTries + 1;
            $correct_streak = $userSkill->correct_streak;
            $fail_streak = $userSkill->fail_streak;
            $skill_passed = $userSkill->skill_passed;
        } else {
            $fail_streak = $correct_streak = $noOfTries = $difficulty_passed =$skill_passed =0;
        }

        $record =[
            'skill_test_date' => new DateTime('now'),
            'skill_passed' => TRUE,
            'difficulty_passed' => Config::get('app.difficulty_levels'),
            'skill_maxile' => max($userSkill->skill_maxile, $track->level->end_maxile_level),
            'noOfTries'=> $noOfTries,
            'correct_streak'=>max(0,$correct_streak),
            'fail_streak'=> max(0,$fail_streak)];
        return $this->users()->sync([$userid=>$record]);
    }

    public function users_failed(){
        return $this->users()->wherePivot('skill_passed','=',FALSE)->wherePivot('fail_streak','>',3)->get();
    }
}
