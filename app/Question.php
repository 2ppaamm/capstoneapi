<?php

namespace App;

use Illuminate\Database\Eloquent\Model;
use App\Log;
use App\ErrorLog;
use DateTime;

class Question extends Model
{
    use RecordLog;    
//    protected static $recordEvents = ['created'];    overriding what is to be logged
    
    protected $hidden = ['user_id', 'created_at', 'updated_at','pivot'];
    protected $fillable = ['user_id','skill_id','difficulty_id','question', 'type_id','status_id', 'answer0', 'answer1', 'answer2', 'answer3', 'answer4', 'correct_answer', 'source', 'question_image','answer0_image','answer1_image','answer2_image','answer3_image','answer4_image','calculator'];

    //relationship
    public function author() {                        //who created this question
        return $this->belongsTo(User::class, 'user_id');
    }

    public function difficulty(){
        return $this->belongsTo(Difficulty::class);
    }

    public function skill() {
        return $this->belongsTo(Skill::class);
    }

    public function status() {
        return $this->belongsTo(Status::class);
    }

    public function type() {
        return $this->belongsTo(Type::class);
    }

    public function solutions(){
        return $this->hasMany(Solution::class);
    }

    public function quizzes(){
        return $this->belongsToMany(Quiz::class)->withTimestamps();
    }

    public function users(){
        return $this->belongsToMany(User::class)->withPivot('question_answered', 'answered_date','correct', 'test_id','attempts')->withTimestamps();
    }

    public function tests(){
        return $this->belongsToMany(Test::class, 'question_user')->withPivot('question_answered', 'answered_date','correct', 'user_id','attempts')->withTimestamps();
    }

    public function attempts($userid){
        return $this->users()->whereUserId($userid)->select('attempts')->first()->attempts;
    }

    /*
     *  Assigns skill to users, questions to users, questions to test
     */
    public function assigned($user, $test){
        $this->skill->users()->sync([$user->id], false);
        $this->tests()->sync([$test->id =>['user_id'=>$user->id]], false);
        return $test;
    }

    public function answered($user, $correctness, $test){
        $record = ['question_answered' => TRUE,
            'answered_date' => new DateTime('now'),
            'correct' =>$correctness,
            'attempts' => $this->attempts($user->id) + 1];
        return $this->users()->sync([$user->id=>$record]);
    }
}