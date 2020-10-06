<?php

namespace App;

use Illuminate\Database\Eloquent\Model;
use DateTime;
use Illuminate\Support\Facades\Mail;

class Quiz extends Model
{
    use RecordLog;

    protected $table = 'quizzes';

    protected $guarded = [];

//    protected $fillable = ['quiz'];

    /**
     * The attributes excluded from the model's JSON form.
     *
     * @var array
     */
//    protected $hidden = ['created_at'];

//    public function questions()
//    {
//        return $this->belongsToMany(Question::class)->withTimestamps()->withPivot(['answered','date_answered','correct']);
//    }

    public function activities()
    {
        return $this->morphMany(Activity::class, 'classwork');
    }

    public function results()
    {
        return $this->morphMany(Result::class, 'assessment');
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }

//    2020-08-31 chen bing add
    public function questions()
    {
        return $this->belongsToMany(Question::class);

    }

    public function skills()
    {
        return $this->belongsToMany(Skill::class)->withTimestamps();
    }

    public function quizzer()
    {
        return $this->belongsTo(User::class);
    }

    public function quizzees()
    {
        return $this->belongsToMany(User::class, 'quiz_user')->withPivot('quiz_completed','completed_date', 'result', 'attempts')->withTimestamps();
    }


    public function attempts($userid){
        return $this->quizzees()->whereUserId($userid)->select('attempts')->first();
    }

    public function houses()
    {
        return $this->belongsToMany(House::class)->withTimestamps();
    }

    public function fieldQuestions($user, $house){
        $questions = collect([]);
        $current_house = $house;
        $untaught_tracks = collect([]);      
//return $this;
//return count($user->correctQuestions);
        if ($user->quizzes()->where('quiz_id',$this->id)->first()->pivot->quiz_completed){
            return response()->json(['message'=>'Quiz has completed', "code"=>500], 500);
        }

        if (count($this->questions)<1) {
            if (!$this->diagnostic) {
                if (count($current_house->current_track)>0){
                   $current_track_questions =  Question::whereIn('skill_id', Skill_Track::whereTrackId($current_house->current_track()->pluck('id'))->pluck('skill_id'))->get();
//return count($current_track_questions);
                    $untaught_tracks = $current_house->tracks->diff($current_house->current_track);
                    $questions = $current_track_questions->diff($user->correctQuestions);
//return count($questions);
                }

                if (count($questions)<10) {
                    if (count($current_house->taught_tracks)>0){
                        $taught_tracks_questions = Question::whereIn('skill_id', Skill_Track::whereIn('track_id',$current_house->taught_tracks()->pluck('id'))->pluck('skill_id'))->get();
                        $untaught_tracks = $untaught_tracks->diff($current_house->taught_tracks)->pluck('id');
                        $questions = count($taught_tracks_questions) > 0 ? $questions->merge($taught_tracks_questions)->diff($user->correctQuestions) : $questions;
//return count($questions);
                    }
                }

                if (count($questions)<10){
                    $untaught_tracks_questions = Question::whereIn('skill_id', Skill_Track::whereIn('track_id',$untaught_tracks)->pluck('skill_id'))->get();
                    $questions = (count($untaught_tracks_questions) > 0) ? $questions->merge($untaught_tracks_questions)->diff($user->correctQuestions):$questions;
                }

                if (count($questions)<10) {
                    $all_tracks_questions = Question::whereIn('skill_id', Skill_Track::whereIn('track_id',$current_house->tracks()->pluck('id'))->pluck('skill_id'))->get()->random(10-count($questions));
                    $questions = (count($all_tracks_questions)>0) ? $questions->merge($all_tracks_questions) : $questions;
                }
//return count($questions);
            
                $questions = count($questions) < 10 ? $questions->merge(Question::all()->random(10-count($questions))) : $questions->random(10);

            } else $questions = \App\Question::whereIn('skill_id',$current_house->skills()->pluck('id'))->whereSource('diagnostic')->get();
//return count($questions);
            foreach ($questions as $question) {
                $question->assignQuiz($user,$this, $current_house);
            }
        }
        else {  
            if (count($user->myQuestions()->whereQuizId($this->id)->get()) > 0) {
                $questions = $user->unansweredQuestions()->whereQuizId($this->id)->get();
            } else {
                $questions = $this->questions;
                foreach ($questions as $question) {
                    $question->assignQuiz($user,$this, $current_house);
                }                
            }
        }

        $quizcomplete = count($questions) < 1 ?  TRUE : FALSE;
        if ($quizcomplete) {
            $message = $this->diagnostic? "Congratulations! Your Diagnostic Test. ".$this->quiz." is successfully completed.  Upon your next login, you will be going into your daily quiz/practice." : "Daily quiz ".$this->quiz.' completed successfully. Join our Adaptive Math program to earn kudos and realize your math potential. For detailed reports on results, please contact us at kang@allgifted.com.';
            return $this->completeQuiz($message, $user);                
        }
                
 
        /* Finding the 5 questions to return:
         * 1. If !$question_user->attempts>0, $questions = !$question_user->attempts 
         * 2. If no question in !question_quiz,
         *    a. if $quiz_user->completed, return error, 500.
         *    b. If $quiz->diagnostic, find $questions with skill_id in tracks in $user->enrolledClasses 
         *       with $question->source = "diagnostic". 
         *    c. If quiz is not diagnostic, and $questions<10, where $question->source != "diagnostic" and in
         *       this priority:
         *      i. Questions either not present in $question_quiz_user or !$question_quiz_user->correct
         *         (previous quizzes) that have skill_id belonging to a track with valid date: today between 
         *         $house_track->start_date and end_date
         *      ii. if count($questions)<10 after (a), then find questions with skill_id in track where 
         *          $housetrack->end_date < today, $user_skill->skill_passed & !$question_quiz_user->correct
         *      iii. if count($questions)<10 after (b), then find any questions with skill_id in track 
         *          where $housetrack->end_date < today and in skill where !$user_skill->skill_passed
         *    d. When count($questions)>=10:
         *       i. $questions->take(10)
         *       ii. fill skill_skill, user_track and question_quiz with the related skill, track, quiz 
         *           and question information.
         *  2. if count($questions)>5 return $questions->take(5) else return $questions to front end
         * 
         */       
        return response()->json(['message' => 'Questions fetched', 'quiz'=>$this->id, 'questions'=>$questions->take(5), 'code'=>201]);
    }

    public function completeQuiz($message, $user){
        $attempts = $this->attempts($user->id);
        $attempts = $attempts ? $attempts->attempts : 1;
        $result = $user->calculateQuizScore($this);
        $this->quizzees()->sync([$user->id=>['quiz_completed'=>TRUE, 'completed_date'=>new DateTime('now'), 'result'=>$result, 'attempts'=> $attempts + 1]]);
        if (count($user->incorrectQuestions) < 1) {
            $note = 'Dear '.$user->firstname.',<br><br>We have detected that you are very diligent in your work and you have done every question right for the topics we have taught. You have shown signs of giftedness and mathematics knowledge beyond your years, and we would like to recommend programs that will enhance your skills . Please contact us at math@allgifted.com<br><br>Thank you. <br><br> <i>This is an automated machine generated by the All Gifted System.</i>';

            Mail::send([],[], function ($message) use ($user,$note) {
                $message->from('info.allgifted@gmail.com', 'All Gifted Admin')
                        ->to($user->email)->cc('kang@allgifted.com')
                        ->subject('Successful Enrolment')
                        ->setBody($note, 'text/html');
            });            

        } 
        return response()->json(['message'=>$message, 'Quiz'=>$this->quiz, 'percentage'=>$result, 'score'=>'not calculated', 'maxile'=> 'AllReady Program : not being calculated','kudos'=>'not eligible yet for', 'code'=>206], 206);
    }
}
