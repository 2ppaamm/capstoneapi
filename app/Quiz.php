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
        return $this->belongsToMany(QuestionQuiz::class);
    }

    public function skills()
    {
        return $this->belongsToMany(QuizSkill::class);
    }

    public function quizzer()
    {
        return $this->belongsTo(User::class);
    }

    public function quizzees()
    {
        return $this->belongsToMany(User::class, 'quiz_user')->withPivot('completed', 'result', 'attempts', 'last_test_date')->withTimestamps();
    }

    public function houses()
    {
        return $this->belongsToMany(HouseQuiz::class, 'house_quiz')->withTimestamps();
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
}
