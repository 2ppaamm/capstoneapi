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
        return $this->belongsToMany(Quiz::class)->withPivot('date_answered','correct')->withTimestamps();
    }

    public function users(){
        return $this->belongsToMany(User::class)->withPivot('question_answered', 'answered_date','correct', 'test_id','attempts')->withTimestamps();
    }

    public function tests(){
        return $this->belongsToMany(Test::class, 'question_user')->withPivot('question_answered', 'answered_date','correct', 'user_id','attempts')->withTimestamps();
    }

    public function attempts($userid){
        $num_attempts =$this->users()->whereUserId($userid)->select('attempts')->first(); 
        return $num_attempts ? $num_attempts->attempts:1;
    }

    public function correctness($user, $answers){
            $correctness = FALSE;
            if ($this->type_id == 2) {
                $correct3 = sizeof($answers) > 3 ? $answers[3] == $this->answer3 ? TRUE : FALSE : TRUE;
                $correct2 = sizeof($answers) > 2 ? $answers[2] == $this->answer2 ? TRUE : FALSE : TRUE;
                $correct1 = sizeof($answers) > 1 ? $answers[1] == $this->answer1 ? TRUE : FALSE : TRUE;
                $correct = sizeof($answers) > 0 ? $answers[0] == $this->answer0 ? TRUE : FALSE : TRUE;
                $correctness = $correct + $correct1 + $correct2 + $correct3 > 3? TRUE: FALSE;
            } else $correctness = $this->correct_answer != $answers ? FALSE:TRUE;
        return $correctness;
    }

    public function answered($user, $correctness, $test, $quiz){
        $record = ['question_answered' => TRUE,
            'answered_date' => new DateTime('now'),
            'correct' =>$correctness,
            'test_id' => $test ? $test->id : null,
            'quiz_id' => $quiz ? $quiz->id : null,
            'attempts' => $this->attempts($user->id) + 1];
        return $this->users()->sync([$user->id=>$record], false);
    }

    /*
     *  Assigns skill to users, questions to users, questions to test
     */
    public function assigned($user, $test){
        $this->skill->users()->sync([$user->id], false);
        $this->tests()->sync([$test->id =>['user_id'=>$user->id]], false);
        $test->skills()->sync([$this->skill_id], false);
        return $test;
    }

    /*
     *  Assigns skill to users, questions to users, questions to quiz, quiz to user.
     */
    public function assignQuiz($user, $quiz, $house){
        $this->users()->sync([$user->id=>['quiz_id'=>$quiz->id]], false);
        $this->skill->users()->sync([$user->id], false);
        $this->quizzes()->sync([$quiz->id], false);
        $quiz->skills()->sync([$this->skill_id], false);
        $track = $this->skill->tracks()->pluck('id')->intersect($house->tracks()->pluck('id'));
        $user->testedTracks()->sync($track, false);
        return $quiz;
    }
}