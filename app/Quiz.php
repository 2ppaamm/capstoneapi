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

        /* 1. if no question in question_quiz_user for this quiz, fill user_skill and user_track
         * 2. Send five unattempted questions at a time to front end
         * 3. When no more unattempted questions in quiz, mark quiz as complete
         * 4. When quiz is completed, calculate scores in %, show which skills passed and which failed.
         * 5. If quiz is diagnostic, find all questions related to house enrolled in. If not diagnostic, 
         *    find questions from enrolled houses->unexpired tracks.
         */       
        return response()->json(['message' => 'Questions fetched', 'quiz'=>$this->id, 'questions'=>$questions, 'code'=>201]);
    }

}
