<?php

namespace App;

use Illuminate\Database\Eloquent\Model;
use DateTime;
use Config;
use App\Question;
use App\Jobs\ProcessQuestionAssignment;

class Test extends Model
{
    use RecordLog;
    
    protected $hidden = ['created_at','updated_at'];
    protected $fillable = ['test', 'description', 'diagnostic', 'number_of_tries_allowed','start_available_time', 'end_available_time','due_time','result','image', 'status_id'];

    //relationship
    public function questions(){
        return $this->belongsToMany(Question::class, 'question_user')->withPivot('correct','question_answered','answered_date', 'attempts', 'user_id')->withTimestamps();
    }
 
    public function user() {
        return $this->belongsTo(User::class);
    }

    public function skills() {
        return $this->belongsToMany(Skill::class)->withTimestamps();
    }

    public function users(){
        return $this->belongsToMany(User::class, 'question_user')->withPivot('correct','question_answered','answered_date', 'attempts', 'question_id','kudos')->withTimestamps();
    }

    public function testee(){
        return $this->belongsToMany(User::class, 'test_user')->withPivot('test_completed', 'completed_date', 'result', 'attempts', 'kudos')->withTimestamps();
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

    public function question_answered(){
        return $this->questions()->whereQuestionAnswered(TRUE);
    }

    public function diagnostic_error(){
        return $this->question_answered()->orderBy('created_at','desc')->take(10);
    }

    public function uncompletedQuestions(){
        return $this->questions()->wherePivot('question_answered',FALSE);
    }

    public function attempts($userid){
        return $this->users()->whereUserId($userid)->select('attempts')->first();
    }

    public function markTest($userid){
        return (count($this->questions)) ? number_format($this->questions()->sum('correct')/(count($this->questions)) * 100, 2, '.', '') : 0;
    }

    protected function initializeLevel($user){
        // If $this->level_id is not set or zero, calculate from maxile_level; otherwise use existing $this->level_id
        $level = $this->level_id ?: (intdiv($user->maxile_level, 100) ?: 2);
        $this->level_id = $level;
        if (!Level::find($level)) {
            // Handle the case where no corresponding level exists
            throw new \Exception("Level with ID {$level} does not exist.");
        }
        $this->save();
        return Level::find($level);
    }

    protected function getDiagnosticQuestionsbyLevel($user, $level){
        // Load the level with question from every tracks and every skill randomly

        $levelQuestions = collect([]);

        // Iterate through each track and their skills to randomly pick one question
        foreach ($level->tracks as $track) {
            foreach ($track->skills as $skill) {
                // Check if there are questions available for the skill
                if ($skill->questions->isNotEmpty()) {
                    // Select a random question
                    $skillQuestion = $skill->questions->random(); 

                    // Store the question as required
                    $levelQuestions->push($skillQuestion);
                }
            }
        }

        return $levelQuestions;
    }

    protected function getAdaptiveQuestions($user, $level){
        $unattemptedQuestions=collect([]);
        $questionsPerTest = Config::get('app.questions_per_test') - 1;
        $numToField=Config::get('app.questions_per_quiz');
        $unansweredQuestions = $this->uncompletedQuestions;

        // 1. Get skills that the user attempted but did not pass
        if (count($unansweredQuestions) < $numToField ){
            $unpassedQuestions = Question::whereSkillId($user->skilluser()->wherePivot('skill_passed', false)->pluck('id'))->take($numToField - count($unansweredQuestions))->get();
        }

        // 2. Get unattempted skills
        if (count($unansweredQuestions) + count($unpassedQuestions) < $numToField){
            $houseIds = $user->validEnrolment(Course::where('course', 'LIKE', '%Math%')->pluck('house_id'));
            $trackIds = House_Track::whereIn('house_id', $houseIds)->pluck('track_id');
            $skillIds = Skill_Track::whereIn('track_id', $trackIds)->pluck('skill_id');
            $unattemptedQuestions = Question::whereIn('skill_id', $skillIds) ->inRandomOrder()->take($numToField - count($unansweredQuestions) - count($unpassedQuestions))->get();
        }

        return $additionalQuestions = $unpassedQuestions->merge($unattemptedQuestions);
    }


    public function fieldQuestions($user) {
        $level = null;
        $currentQuestions = $this->questions;
        $newQuestions = null;
        $fieldQuestions = null;
        $questionsPerTest = Config::get('app.questions_per_test') - 1;
        $numToField = Config::get('app.questions_per_quiz');
        $unansweredQuestions = $this->uncompletedQuestions;
        // Check if more questions are needed.
        if (count($unansweredQuestions) < $numToField) {
            $level = $this->initializeLevel($user)->id;

            if ($this->diagnostic) {
                $newQuestions = $this->getDiagnosticQuestionsbyLevel($user, Level::find($level));
            } else {
                $newQuestions = $this->getAdaptiveQuestions($user, $level);
            }
            // Assign new questions if any.
            if ($newQuestions->isNotEmpty()) {     
                $unansweredQuestions = $unansweredQuestions->merge($newQuestions);
                ProcessQuestionAssignment::dispatch($newQuestions->pluck('id'), $this->id, $user->id);
            }
        }
        $fieldQuestions = $unansweredQuestions->take($numToField);
        if ($fieldQuestions->isEmpty()) {
            $message = "Test Completed, no more questions.";
            return $this->completeTest($message, $user);
        } else {
            return [
                'test' => $this->id,
                'questions' => $fieldQuestions
            ];
        }
    }




 /*   public function fieldQuestions($user){
        $level = null;
        $questions = collect([]);
        $message = '';
        if (!count($this->uncompletedQuestions)) {    // no more questions
            if ($this->diagnostic) {             // if diagnostic check new level, get qns
//                $stop_test = $this->diagnostic_error()->whereCorrect(FALSE)->count()>=5 ? True : False; //check if user is testing below 
                //Initiate level
return                $this->level_id =  (!count($this->questions) || !$this->level_id) ? 2: $this->level_id;
                $this->save();

                try {
                     $level = Level::find($this->level_id);
                } catch (Exception $e) { 
                    return $this->completeTest('Cannot find level to test.',$user);
                }
                
                if ($stop_test || $level->start_maxile_level >600){ //if user already exceeded current level to test
                    if (count($this->questions) == count($this->questions()->where('question_answered','>=','1')->get())) {
                        $message = "Diagnostic test completed";
                        return $this->completeTest($message, $user);
                    }                        
                }
                
                // get questions, then log track, assign question to user
                foreach ($level->tracks as $track) {  //diagnostic => 1 track 1 question
                    $randomQuestion = Question::whereIn('skill_id', $track->skills->pluck('id'))
                        ->with(['skill' => function($query) use ($track) {
                            $query->where('track_id', $track->id)
                        ->withPivot('doneNess');
                              }])
                        ->orderBy('difficulty_id', 'desc')
                        ->inRandomOrder()
                        ->first(['questions.*']);

                    if ($randomQuestion) {
                        $randomQuestion->doneNess = $randomQuestion->skill->pivot->doneNess ?? null;
                        $questions->push($randomQuestion);
                    }
//                    $questions = $questions->merge(Question::whereIn('skill_id', $track->skills->pluck('id'))->orderBy('difficulty_id','desc')->inRandomOrder()->take(1)->get()); 
                    $track->users()->sync([$user->id], false);        //log tracks for user
                }              
            } elseif (!count($this->questions)) {           // not diagnostic, new test
                $level = Level::whereLevel(max(200,min(700,round($user->maxile_level/100)*100)))->first();                                // get user general level
                $this->level_id = $level->id;
                $this->save();

                $user->testedTracks()->sync($level->tracks()->pluck('id')->toArray(), false);
                $tracks_to_test = count($user->tracksFailed) ? $level->tracks->intersect($user->tracksFailed) ? $level->tracks->intersect($user->tracksFailed) : $user->tracksFailed : null;                         // test failed tracks
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
                        ->take(Config::get('app.questions_per_quiz'))->get();
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
                    $questions = Question::inRandomOrder()->take(Config::get('app.questions_per_test'))->get();
                }
            }

            foreach ($questions as $question){
                $question ? $question->assigned($user, $this) : null;
            }
        }

        $new_questions = $this->uncompletedQuestions()->with('skill.tracks')->get();

        if (!count($new_questions) && count($this->questions)) { //no new question and this user has already tested
//        if (count($this->questions()->get()) <= $this->questions()->sum('question_answered')){
            $message = $this->description.' ended successfully';
            return $this->completeTest($message, $user);
        }
//        }
        // field unanswered questions
        $test_questions = count($new_questions)< Config::get('app.questions_per_quiz')+1 ? $new_questions : $new_questions->take(Config::get('app.questions_per_quiz'));


        return response()->json(['message' => 'Request executed successfully', 'test'=>$this->id, 'questions'=>$test_questions, 'code'=>201]);
    }
*/
    public function fieldDiagnosticQuestions($course) {
        $randomQuestions = collect();

        // Fetch tracks associated with the course
        $tracks = $course->tracks;

        foreach ($tracks as $track) {
            // Fetch skill IDs associated with this track
            $skillIds = $track->skills->pluck('id');

            // Fetch one random question from any of these skills
            $randomQuestion = Question::whereIn('skill_id', $skillIds)
                ->inRandomOrder() // Order by random
                ->first(); // Take the first one after randomizing

            if ($randomQuestion) {
                $randomQuestions->push($randomQuestion);
            }
        }

        return $randomQuestions;
    }

    public function completeTest($message, $user){
        $attempts = $this->attempts($user->id);
        $attempts = $attempts ? $attempts->attempts : 0;
        $maxile = $user->calculateUserMaxile($this);
        $user->enrolclass($maxile); //enrol in class of maxile reached
        $kudos_earned = $this->testee()->first()->pivot->kudos;
        $user->game_level = $user->game_level + $kudos_earned;  // add kudos
        $user->diagnostic = FALSE;
        $user->save();  
        //save maxile and game results
        $this->testee()->sync([$user->id=>['test_completed'=>TRUE, 'completed_date'=>new DateTime('now'), 'result'=>$result = $this->markTest($user->id), 'attempts'=> $attempts + 1]]);

        return response()->json(['message'=>$message, 'test'=>$this->id, 'percentage'=>$result, 'score'=>$maxile, 'maxile'=> $maxile,'kudos'=>$kudos_earned,'code'=>206], 206);
    }
}