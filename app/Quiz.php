<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

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
//        return $this->belongsToMany(QuestionQuiz::class);
        return $this->hasMany(QuestionQuiz::class);

    }

    public function skills()
    {
//        return $this->belongsToMany(QuizSkill::class);
        return $this->hasMany(QuizSkill::class);
    }

    public function quizzer()
    {
        return $this->belongsTo(User::class);
    }

    public function quizzees()
    {
        return $this->belongsToMany(User::class, 'quiz_user')->withPivot('quiz_completed','completed_date', 'result', 'attempts')->withTimestamps();
    }

    public function houses()
    {
//        return $this->belongsToMany(HouseQuiz::class, 'house_quiz')->withTimestamps();
//        return $this->hasMany(HouseQuiz::class, 'house_quiz')->withTimestamps();
        return $this->hasMany(HouseQuiz::class);
    }

    public function user_questions()
    {
        return $this->belongsToMany(User::class, 'question_quiz_user')->withPivot('attempts', 'correct', 'question_answered', 'question_id', 'answered_date')->withTimestamps();
    }

    public function unansweredQuestions($user_id)
    {
        return $this->user_questions()->whereAttempts(FALSE)->whereUser_id($user_id);
    }

    public function answeredQuestions($user_id)
    {
        return $this->user_questions()->where('attempts', '>', '0')->whereUser_id($user_id);
    }

    public function fieldQuestions($user){
        $questions = collect([]);

        // find the questions to send to frontend, send 5 at a time.
        $questions = \App\Question::whereIn('skill_id', House::findorFail($user->enrolledClasses()->first()->house_id)->skills()->pluck('id'))->get();

        /* Finding the 5 questions to return:
         * 1. If !$question_quiz_user->attempts>0, $questions = !$question_quiz_user->attempts 
         * 2. If no question in !question_quiz_user->attempts for this quiz,
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
         *       ii. fill user_skill, user_track and question_quiz_user with the related skill, track, quiz 
         *           and question information.
         *  2. if count($questions)>5 return $questions->take(5) else return $questions to front end
         * 
         */       
        return response()->json(['message' => 'Questions fetched', 'quiz'=>$this->id, 'questions'=>$questions, 'code'=>201]);
    }

}
