<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class SkillUser extends Model
{
    use RecordLog;
    
    protected $table = 'skill_user';
    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = ['skill_id','user_id', 'skill_maxile', 'skill_test_date','skill_passed','difficulty_passed', 'noOfTries', 'correct_streak', 'total_correct_attempts', 'total_incorrect_attempts', 'fail_streaks'];

}
