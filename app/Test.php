<?php

namespace App;

use Illuminate\Database\Eloquent\Model;
use DateTime;
use Config;

class Test extends Model
{
    use RecordLog;
    
    protected $hidden = ['user_id', 'created_at','updated_at'];
    protected $fillable = ['test', 'description', 'diagnostic', 'number_of_tries_allowed','start_available_time', 'end_available_time','due_time','result','image', 'status_id','level_id'];

    //relationship
    public function questions(){
        return $this->belongsToMany(Question::class, 'question_user')->withPivot('correct','question_answered','answered_date', 'attempts', 'user_id')->withTimestamps();
    }

    public function skills() {
        return $this->belongsToMany(Skill::class)->withTimestamps();
    }

    public function users(){
        return $this->belongsToMany(User::class, 'question_user')->withPivot('correct','question_answered','answered_date', 'attempts', 'question_id')->withTimestamps();
    }

    public function testee(){
        return $this->belongsToMany(User::class, 'test_user')->withPivot('test_completed', 'completed_date', 'result', 'attempts')->withTimestamps();
    }

    public function tester(){
        return $this->belongsTo(User::class);
    }

    public function houses(){
        return $this->belongsToMany(Test::class)->withTimestamps();
    }

    public function results(){
        return $this->morphMany(Result::class, 'assessment');
    }

    public function activities(){
        return $this->morphMany(Activity::class, 'classwork');
    }

    public function uncompletedQuestions(){
        return $this->questions()->whereQuestionAnswered(FALSE);
    }

    public function attempts($userid){
        return $this->users()->whereUserId($userid)->select('attempts')->first();
    }

    public function markTest($userid){
        return count($this->questions()->get()) ? number_format($this->questions()->sum('correct')/count($this->questions()->get()) * 100, 2, '.', '') : 0;
    }

    public function fieldQuestions($user){
        $level = null;
        $questions = collect([]);
        $message = '';
        if (!count($this->uncompletedQuestions)) {    // no more questions
            if ($this->diagnostic) {                  // if diagnostic check new level, get qns
            if (count($this->questions) && $this->level_id) {        // if there are questions or ongoing test
                try {
                    $level = Level::where('level', '=', ($this->level_id)*100)->first(); //find the next level
                    $this->level_id = $level->id;                    
                } catch (Exception $e) { 
                    return $this->completeTest('No more level to test.',$user);}
                if ($user->maxile_level > $level->start_maxile_level || $level->start_maxile_level >600){ //if user already exceeded current level to test
                    if (count($this->questions) == count($this->questions()->where('question_answered','>=','1')->get())) {
                        $message = "Diagnostic test completed";
                        return $this->completeTest($message, $user);
                    }                        
                }
  
            } else {
                $level=Level::find(2);
                $this->level_id = $level->id;
            }
            $this->save();
            
            // get questions, then log track, assign question to user
            foreach ($level->tracks as $track) {  //diagnostic => 1 track 1 question
                $questions = $questions->merge(Question::whereIn('skill_id', $track->skills->pluck('id'))->orderBy('difficulty_id','desc')->inRandomOrder()->take(1)->get()); 
                $track->users()->sync([$user->id], false);        //log tracks for user
            }              
        } elseif (!count($this->questions)) {           // not diagnostic, new test
            $level = max(min(Level::find(7), Level::whereLevel(round($user->maxile_level/100)*100)->first()), Level::find(2));  // get userlevel
            $this->level_id = $level->id;
            $this->save();

            $user->testedTracks()->sync($level->tracks()->pluck('id')->toArray(), false);
            $tracks_to_test = count($user->tracksFailed) ? !$level->tracks->intersect($user->tracksFailed) ? $level->tracks->intersect($user->tracksFailed) : $user->tracksFailed : $level->tracks;                         // test failed tracks
            if (count($tracks_to_test) < Config::get('app.tracks_to_test')) {  
                $next_level = Level::where('level','>',$level->level)->first();
                $tracks_to_test = $tracks_to_test->merge($next_level->tracks()->get());
            }
            $i = 0;
            while (count($questions) < Config::get('app.questions_per_test') && $i < count($tracks_to_test)) {
                $tracks_to_test[$i]->users()->sync([$user->id], false);          //log tracks for user
                $skills_to_test = $tracks_to_test[$i]->skills()->pluck('id')->toArray();               
                $user->skill_user()->sync($skills_to_test, false);
                $skills_to_test = $tracks_to_test[$i]->skills->intersect($user->skill_user()->whereSkillPassed(FALSE)->get());
                $n = 0;
                while (count($questions) < Config::get('app.questions_per_test') && $n < count($skills_to_test)){
                    $difficulty_passed = $skills_to_test[$n]->users()->whereUserId($user->id)->first() ? $skills_to_test[$n]->users()->whereUserId($user->id)->select('difficulty_passed')->first()->difficulty_passed : 0;
                    //find 5 questions in the track that are not already fielded and higher difficulty if some difficulty already passed
                    $skill_questions = Question::inRandomOrder()->whereSkillId($skills_to_test[$n]->id)->where('difficulty_id','>', $difficulty_passed)
                    //->whereNotIn('id', $user->myQuestions()->pluck('question_id'))
                    ->take(5)->get();
                    if (count($skill_questions)){
                        $questions = $skill_questions->merge($questions);
                    } else {
                        $skill_user = $skills_to_test[$n]->forcePass($user->id, $difficulty_passed+1, $tracks_to_test[$i]);
                    }
                    $n++;           
                }
                $i++;
            }
            if (!count($questions)) {
                $questions = Question::inRandomOrder()->take(20)->get();
            }
        }

            foreach ($questions as $question){
                $question ? $question->assigned($user, $this) : null;
            }            
        }

        $new_questions = $this->uncompletedQuestions()->get();

        if (!count($new_questions) && count($this->questions)) { //no new question and this user has already tested
//        if (count($this->questions()->get()) <= $this->questions()->sum('question_answered')){
            $message = 'Test ended successfully';
            return $this->completeTest($message, $user);
        }
//        }
        // field unanswered questions
        $test_questions = count($new_questions)< Config::get('app.questions_per_quiz')+1 ? $new_questions : $new_questions->take(Config::get('app.questions_per_quiz'));
        return response()->json(['message' => 'Request executed successfully', 'test'=>$this->id, 'questions'=>$test_questions, 'code'=>201]);
    }

    public function completeTest($message, $user){
        $attempts = $this->attempts($user->id);
        $attempts = $attempts ? $attempts->attempts : 0;
        $maxile = $user->calculateUserMaxile($this);
        $user->enrolclass($maxile);                          //enrol in class of maxile reached
        $kudos_earned = $this->questions()->sum('correct');
        $user->game_level = $user->game_level + $kudos_earned;  // add kudos
        $user->save();                                          //save maxile and game results
        $this->testee()->sync([$user->id=>['test_completed'=>TRUE, 'completed_date'=>new DateTime('now'), 'result'=>$result = $this->markTest($user->id), 'attempts'=> $attempts + 1]]); 
        return response()->json(['message'=>$message, 'test'=>$this->id, 'percentage'=>$result, 'score'=>$maxile, 'maxile'=> $maxile,'kudos'=>$kudos_earned, 'code'=>206], 206);
    }
}