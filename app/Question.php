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

    public function level() {
        return $this->track->level();
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
        return $this->belongsToMany(User::class, 'question_user')->withPivot('question_answered', 'answered_date','correct', 'test_id','attempts')->withTimestamps();
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
    $record = [
            'question_answered' => TRUE,
            'answered_date' => new DateTime('now'),
            'correct' => $correctness,
            'test_id' => $test ? $test->id : null,
            'quiz_id' => $quiz ? $quiz->id : null,
            'attempts' => $this->attempts($user->id) + 1
        ];
        return $this->users()->updateExistingPivot($user->id, $record);
    }

    /*
     *  Assigns skill to users, questions to users, questions to test, skills to test, tracks to the test, note that test-user is already assigned when test was created.
     */
    public function assigned($user, $test)
    {
        // 1. Assign question to user in question_user with test-specific data
        $this->users()->syncWithoutDetaching([
            $user->id => [
                'test_id' => $test->id,
                'question_answered' => false,
                'correct' => false,
                'attempts' => 0,
            ],
        ]);

        // 2. Assign skill to user in skill_user (if not already present)
        $user->skilluser()->syncWithoutDetaching([
            $this->skill_id => [
                'skill_test_date' => now(),
                'skill_passed' => 0,
                'difficulty_passed' => 0,
                'noOfTries' => 0,
                'noOfPasses' => 0,
                'noOfFails' => 0,
            ],
        ]);

        // 3. Assign question to test in question_user (via tests() relation)
        $this->tests()->syncWithoutDetaching([
            $test->id => ['user_id' => $user->id],
        ]);

        // 4. Assign skill to test in skill_test (if not already attached)
        $test->skills()->syncWithoutDetaching([$this->skill_id]);

        // 5. Assign track(s) to user in track_user
        $tracks = Skill::find($this->skill_id)?->tracks ?? collect();
        foreach ($tracks as $track) {
            $user->testedTracks()->syncWithoutDetaching([
                $track->id => [
                    'track_maxile' => 0.00,
                    'track_passed' => 0,
                    'track_test_date' => now(),
                    'doneNess' => 0.00,
                ]
            ]);
        }

        return $test->fresh();
    }

    /*
     *  Assigns skill to users, questions to users, questions to quiz, quiz to user.
     */
    public function assignQuiz($user, $quiz, $house){
        $user->myQuestions()->attach([$this->id=>['quiz_id'=>$quiz->id]]);
        $user->skill_user()->sync([$this->skill_id],false);
        $this->quizzes()->sync([$quiz->id],false);
        $quiz->skills()->sync([$this->skill_id], false);
        $track = $this->skill->tracks()->pluck('id')->intersect($house->tracks()->pluck('id'));
        $user->testedTracks()->syncWithoutDetaching($tracks);
        return $quiz;
    }
}