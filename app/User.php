<?php

namespace App;

use App\Role;
use App\User;
use DB;
use Auth;
use Illuminate\Auth\Authenticatable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Auth\Passwords\CanResetPassword;
use Illuminate\Foundation\Auth\Access\Authorizable;
use Illuminate\Contracts\Auth\Authenticatable as AuthenticatableContract;
use Illuminate\Contracts\Auth\Access\Authorizable as AuthorizableContract;
use Illuminate\Contracts\Auth\CanResetPassword as CanResetPasswordContract;
use DateTime;

class User extends Model implements AuthenticatableContract,
                                    AuthorizableContract,
                                    CanResetPasswordContract
{
    use Authenticatable, Authorizable, CanResetPassword, HasRoles, RecordLog;


    /**
     * The database table used by the model.
     *
     * @var string
     */
    protected $table = 'users';
    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = ['name','firstname', 'lastname', 'email','image', 'maxile_level', 'game_level','mastercode','contact', 'password', 'date_of_birth'];

    /**
     * The attributes excluded from the model's JSON form.
     *
     * @var array
     */
    protected $hidden = ['password', 'remember_token', 'created_at'];

    // make dates carbon so that carbon google that out
    protected $dates = ['date_of_birth', 'last_test_date', 'next_test_date'];

    // relationships
    public function mastercodes(){
        return $this->hasMany(Mastercode::class);
    }

    public function questions() {                        // question setter
        return $this->hasMany(Question::class);
    }

    public function difficulties() {
        return $this->hasMany(Difficulty::class);
    }

    public function levels() {
        return $this->hasMany(Level::class);                // owns many levels
    }

    public function courses() {
        return $this->hasMany(Course::class);
    }

    public function houses() {
        return $this->hasMany(House::class, 'house_id');
    }

    public function tracks() {
        return $this->hasMany(Track::class);
    }

    public function skills(){
        return $this->hasMany(Skill::class);              //originator of skills
    }

    public function fields(){
        return $this->belongsToMany(Field::class)->withPivot('field_maxile', 'field_test_date', 'month_achieved')->withTimestamps();
    }

    public function storefieldmaxile($maxile, $field_id){
        $field_user = $this->fields()->whereFieldId($field_id)->whereMonthAchieved(date('Ym', time()))->select('field_maxile')->first();
        $old_maxile = $field_user ? $field_user->field_maxile : 0;

        ($old_maxile < $maxile) ? 
            $this->fields()->sync([$field_id => ['field_maxile'=>$maxile, 'field_test_date'=> new DateTime('now'), 'month_achieved'=>date('Ym', time())]], false) : null;
        return $maxile;
    }

    public function getmyresults(){
        return $this->with('fields.user_maxile')->get();
    }

    public function getfieldmaxile(){
        return $this->belongsToMany(Field::class)->withPivot('field_maxile', 'field_test_date', 'month_achieved')->withTimestamps()->select('field_maxile', 'field_test_date','month_achieved','field', 'id');
    }

    // enrolment
    public function enrolledClasses(){
        return $this->enrolment()->where('expiry_date', '>', date("Y-m-d"))
        ->orderBy('expiry_date','desc');

    }

    public function expiredClasses(){
        return $this->enrolment()->withPivot('role_id')->groupBy('house_id')
        ->where('expiry_date', '<', date("Y-m-d"))
        ->orderBy('expiry_date','desc');
    }

    // Role management
    public function houseRoles(){
        return $this->belongsToMany(Role::class, 'house_role_user')->withPivot('house_id')->withTimestamps();
    }

    public function roleHouse(){
        return $this->belongsToMany(House::class, 'house_role_user')->withPivot('role_id')->withTimestamps();
    }

    public function studentHouse(){
        return $this->roleHouse()->whereRoleId(Role::where('role', 'LIKE', '%Student')->pluck('id'))->groupBy('house_id');
    }

    public function teachHouse(){
        return $this->roleHouse()->whereRoleId(Role::where('role', 'LIKE', '%Teacher')->pluck('id'))->groupBy('house_id');
    }

    public function enrolment(){
        return $this->hasMany(Enrolment::class);
    }

    public function enrolclass($user_maxile){
        $houses = House::whereIn('course_id',Course::where('start_maxile_score','<=' ,round($user_maxile/100)*100)->pluck('id'))->pluck('id')->all();
        foreach ($houses as $house) {
            $houses_id[$house]=['role_id'=>6];
        }
        $this->roleHouse()->sync(1, false);
        return 'enrolment created';
    }

    public function validEnrolment($courseid){
        return $this->enrolment()->whereRoleId(Role::where('role', 'LIKE', '%Student')->pluck('id'))->whereIn('house_id', House::whereIn('course_id', $courseid)->pluck('id'))->where('expiry_date','>=', new DateTime('today'))->get();
    }

    public function teachingHouses(){
        return $this->enrolment()->where('role_id','<',Role::where('role', 'LIKE', '%Teacher')->pluck('id'))->groupBy('house_id');
    }

    //user's roles in selected class
    public function hasClassRole($role, $house){
        $houseRole = $this->houseRoles()->with(['userHouses'=>function($q) use ($house){
            $q->whereHouseId($house)->groupBy('house_id');
        }])->groupBy('id')->whereHouseId($house)->get();

        if (is_string($role)){
            return $houseRole->contains('role', $role);
        }
        return !! $role->intersect($houseRole)->count();
    }

    // maxile logs
    public function fieldMaxile(){
        return $this->belongsToMany(Field::class)->withPivot('field_maxile', 'field_test_date')->select('field', 'field_test_date', 'field_maxile')->withTimestamps();
    }

    public function skill_user(){
        return $this->belongsToMany(Skill::class)->withPivot('skill_test_date','skill_passed','skill_maxile','noOfTries','noOfPasses','difficulty_passed', 'noOfFails');
    }

    public function skillMaxile(){
        return $this->belongsToMany(Skill::class)->withPivot('skill_maxile', 'skill_test_date','noOfTries','noOfPasses','skill_passed','difficulty_passed')->select('skill_id','skill', 'skill_maxile', 'skill_test_date','noOfTries','noOfPasses','skill_passed','difficulty_passed')->groupBy('skill');
    }

    public function completedSkills(){
        return $this->skillMaxile()->whereSkillPassed(True);
    }

    // manage logs
    public function logs(){
        return $this->hasMany(Log::class)->orderBy('updated_at','desc')->take(20);;
    }

    // Tests
    public function writetests(){
        return $this->hasMany(Test::class);
    }

    public function tests(){
        return $this->belongsToMany(Test::class)->withPivot('test_completed','completed_date', 'result', 'attempts')->withTimestamps();
    }

    public function incompletetests(){
        return $this->tests()->whereTestCompleted(0)->where('start_available_time', '<=', new DateTime('today'))->where('end_available_time','>=', new DateTime('today'))->orderBy('created_at','desc');
    }

    public function currenttest(){
        return $this->incompletetests()->take(1);
    }

    public function completedtests(){
        return $this->tests()->whereTestCompleted(1);
    }

    public function myQuestions(){
        return $this->belongsToMany(Question::class)->withPivot('question_answered', 'answered_date','correct','attempts','test_id')->withTimestamps();
    }

    public function unansweredQuestions(){
        return $this->myQuestions()->whereQuestionAnswered(0);
    }

    public function answeredQuestion(){
        return $this->myQuestions()->whereQuestionAnswered(1);
    }

    public function incorrectQuestions(){
        return $this->myQuestions()->whereCorrect(0);
    }

    public function myQuestionPresent($question_id){
        return $this->myQuestions()->whereQuestionId($question_id)->first();
    }

    public function noOfAttempts($question_id){
        return $this->myQuestions()->where('question_id',$question_id)->select('attempts')->first()->attempts; 
    }
    public function quizzes(){
        return $this->hasMany(Quiz::class);
    }

    //query scopes

    public function scopeAge(){
            return date_diff(date_create(Auth::user()->date_of_birth), date_create('today'))->y;
    }

   public function scopeProfile($query, $id) { 
        return $query->whereId($id)->with(['getfieldmaxile','fields.user_maxile','enrolledClasses.roles',
            'enrolledClasses.houses.created_by',//'enrolledClasses.enrolledStudents',
            'enrolledClasses.houses.tracks.track_maxile',
            'enrolledClasses.houses.tracks.skills', 'enrolledClasses.houses.tracks.skills'
            ])->first();
    }

    public function scopeGameleader($query){
        return $query->orderBy('game_level','desc')->select('image','maxile_level','game_level', 'last_test_date as leader_since', 'firstname as name')->take(10)->get();
    }

    public function scopeMaxileleader($query){
        return $query->orderBy('maxile_level','desc')->select('image','maxile_level', 'game_level','last_test_date as leader_since', 'firstname as name')->take(10)->get();        
    }

    public function testedTracks(){
        return $this->belongsToMany(Track::class)->withPivot('track_maxile','track_passed','track_test_date')->withTimestamps();
    }

    public function tracksPassed(){
        return $this->testedTracks()->whereTrackPassed(TRUE);
    }

    public function tracksFailed(){
        return $this->testedTracks()->whereTrackPassed(FALSE);
    }

    public function trackResults(){
        return $this->testedTracks()->pluck('track_maxile');
    }
    // User's current average maxile
    public function scopeUserMaxile($query){
        return \App\FieldUser::whereUserId(Auth::user()->id)->select(DB::raw('AVG(field_maxile) AS user_maxile'))->first()->user_maxile;
    }

    public function scopeHighest_scores($query){
        return $query->addSelect(DB::raw('MAX(maxile_level) AS highest_maxile'),DB::raw('MAX(game_level) AS highest_game'),DB::raw('AVG(game_level) AS average_game'))->first();
    }

    public function errorlogs(){
        return $this->hasMany(ErrorLog::class);
    }

    public function calculateUserMaxile($test){        
//        $level=Track::whereIn('id',\App\Skill_Track::whereIn('skill_id',$test->questions()->pluck('skill_id'))->pluck('track_id')->intersect($this->tracksPassed()->pluck('id')))->max('level_id');     // the highest level of the track passed in this test
//        $highest_level_passed = Level::find($level);        
//        $highest_level_passed = Level::whereIn('id', $this->tracksPassed()->pluck('level_id'))->orderBy('level', 'desc')->first(); //highest level passed in this test
 //       $user_maxile = $highest_level_passed ? number_format(max($this->testedTracks()->whereIn('track_id',$highest_level_passed->tracks()->pluck('id'))->avg('track_maxile'), $highest_level_passed->start_maxile_level), 2,'.','') : 0;
        if ($test->diagnostic){
            $user_maxile = $this->testedTracks()->avg('track_maxile');
        } else {
            if ($test->noOfSkillsPassed > 0){
                $user_maxile = max(min(Level::find($test->level_id)->start_maxile_level,$test->test_maxile/$test->noOfSkillsPassed),$this->maxile_level);
            } else {
                $user_maxile = $this->maxile_level;
            }
        }
        $this->maxile_level = $user_maxile;
        $this->last_test_date = new DateTime('now');
        $this->save();
        return $user_maxile;
    }

    public function accuracy(){
        return $this->myQuestions()->sum('question_answered')? $this->myQuestions()->sum('correct')/$this->myQuestions()->sum('question_answered')*100:0;
    }
}