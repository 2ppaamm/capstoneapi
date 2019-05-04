<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class Track_User extends Model
{
    protected $fillable = ['track_id','user_id','track_maxile','track_passed','track_test_date'];
    protected $table = "track_user";
    protected $hidden = ['created_at', 'updated_at','pivot'];
}
