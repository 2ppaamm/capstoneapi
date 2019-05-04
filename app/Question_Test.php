<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class Question_Test extends Model
{
    protected $table = 'question_user';

    protected $fillable = ['question_id','test_id', 'user_id', 'question_answered','correct','answer_date','attempts'];
    //
}
