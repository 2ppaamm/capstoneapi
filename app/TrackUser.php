<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class TrackUser extends Model
{
    use RecordLog;

    protected $table = 'track_user';
    protected $primary_key = 'track_id';
    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = ['track_id','user_id', 'track_maxile', 'track_test_date','track_passed'];

    /**
     * The attributes excluded from the model's JSON form.
     *
     * @var array
     */
 
    // make dates carbon so that carbon google that out
    protected $dates = ['last_quiz_date'];


}
