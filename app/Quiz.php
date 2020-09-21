<?php

namespace App;

use Illuminate\Database\Eloquent\Model;
use DateTime;

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
//        return $this->hasMany(HouseQuiz::class, 'house_quiz')->withTimestamps();
//        return $this->hasMany(HouseQuiz::class);
    }

    public function fieldQuestions($user, $house){
        $questions = collect([]);
        $current_house = $house;
//        if ($user->quizzes()->where('quiz_id',$this->id)->first()->quiz_completed){
//            return response()->json(['message'=>'Quiz has completed', "code"=>500], 500);
//        }
        if (count($this->questions)<1) {
            if ($this->diagnostic) {
                $questions = \App\Question::whereIn('skill_id',$current_house->skills()->pluck('id'))->get();   
            } else {
                if(count($current_house->current_track)>0){
                    $current_track_questions =  Question::whereIn('skill_id', Skill_Track::whereTrackId($current_house->current_track()->pluck('id'))->pluck('skill_id'))->get();
                    $questions = $current_track_questions->diff($user->correctQuestions);      
                }
                if (count($questions)<10) {
                    if (count($current_house->taught_tracks)>0){
                        $taught_tracks_questions = Question::whereIn('skill_id', Skill_Track::whereTrackId($current_house->taught_tracks()->pluck('id'))->pluck('skill_id'))->get();
                        $questions = $taught_tracks_questions ? $questions->merge($taught_tracks_questions) : $questions;
                    }
                }

                if (count($questions)<10){
                    $untaught_tracks = $current_house->tracks->diff($current_house->taught_tracks)->diff($current_house->current_track)->pluck('id');
                    $questions = Question::whereIn('skill_id', Skill_Track::whereTrackId($untaught_tracks))->get();
                }
                $questions = count($questions) < 1 ? Question::all()->random(10) : $questions->take(10);
            }

            foreach ($questions as $question) {
                $question->assignQuiz($user,$this, $current_house);
            }
   
        } else {
            $questions = $user->unansweredQuestions()->whereQuizId($this->id)->get();
            $quizcomplete = $user->quizzes()->whereQuizId($this->id)->first()->quiz_completed;
            $quizcomplete = (count($user->answeredQuestion()->whereQuizId($this->id)->get()) == count($this->questions)) || count($questions)<1 ? TRUE : FALSE;
            if ($quizcomplete) {
                $message = "Quiz completed successfully. For detailed reports on results, please contact us at kang@allgifted.com.";
                return $this->completeQuiz($message, $user);                
            }
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
        return response()->json(['message'=>$message, 'test'=>$this->id, 'percentage'=>$result, 'score'=>'not calculated', 'maxile'=> 'Not calculated','kudos'=>'Not elgibile for', 'code'=>206], 206);
    }
}
