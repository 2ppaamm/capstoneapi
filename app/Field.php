<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class Field extends Model
{
    use RecordLog;

    protected $hidden = ['user_id', 'created_at', 'updated_at','pivot'];
    protected $fillable = ['field', 'description', 'image', 'status_id'];

    public function user() {                        //who created this track
        return $this->belongsTo(User::class);
    }

    public function users(){
        return $this->belongsToMany(User::class)->withPivot('field_maxile', 'field_test_date', 'month_achieved')->withTimestamps();
    }

    public function status() {
        return $this->belongsTo(Status::class);
    }

    public function tracks(){
        return $this->hasMany(Track::class);
    }

  /*  public function user_maxile(){
        return $this->hasManyThrough(TrackUser::class, Track::class)->select('track_maxile', 'track_passed', 'track_test_date')->where('track_maxile', '>', 0);
    }
*/
    public function videos(){
        return $this->hasMany(Video::class);
    }

}
