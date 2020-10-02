<?php

namespace App;

use DateTime;
use Illuminate\Database\Eloquent\Model;

class Enrolment extends Model
{
    use RecordLog;

    protected $table = 'house_role_user';
    protected $fillable = ['mastercode','purchaser_id','payment_email','user_id','role_id', 'house_id','expiry_date', 'start_date', 'places_alloted', 'progress', 'transaction_id', 'currency_code', 'amount_paid', 'payment_status'];
    /**
     * The attributes excluded from the model's JSON form.
     *
     * @var array
     */
    protected $hidden = ['payment_email'];

    public function users(){
    	return $this->belongsTo(User::class, 'user_id');
    }

    public function roles(){
    	return $this->belongsTo(Role::class, 'role_id');
    }

    public function houses(){
    	return $this->belongsTo(House::class, 'house_id');
    }

    public function purchaser(){
        return $this->belongsTo(User::class, 'purchaser_id');
    }

    public function quizzes(){
        return $this->belongsTo(HouseQuiz::class);
    }
//    public function mastercodes(){
//        return $this->hasOne(Mastercode::class);
 //   }

    public function inforce($user, $course){
        return $this->users()->whereUserId($user->id)->whereIn('house_id', $course->houses()->pluck('id'))->where('expiry_date','>', new DateTime(now))->get();
    }
}
