<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class Video extends Model
{
    public function user(){
        return $this->belongsTo(User::class);              //originator of video
    }

    public function status(){
    	return $this->hasOne(Status::class);
    }
    public function skills() {
    	return $this->belongsToMany(Skill::class, 'skilllinks')->withPivot('status_id','user_id', 'link')->withTimestamps();
    }
}
