<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

use App\Http\Requests;
use App\Level;
use App\Question;
use App\Quiz;
use Auth;
use App\Http\Requests\CreateQuizAnswersRequest;
use DateTime;
use App\User;
use Config;
use App\Error;
use App\Course;
use App\Enrolment;
use App\Role;
use App\Http\Requests\StoreMasterCodeRequest;
use Illuminate\Support\Facades\Mail;
use Carbon\Carbon;

class DiagnosticController extends Controller
{
    public function __construct(){
//	$this->middleware('cors');
    }

    /**
     *
     * One question from the highest skill of each track from the appropriate level
     *
     * @return \Illuminate\Http\Response
     */
    public function index(){
        $courses = Course::where('course', 'LIKE', '%Math%')->pluck('id'); //any math course id
        $allreadycourses = Course::where('course','LIKE','%AllReady%')->pluck('id'); //all ready course id
        $quiz=[];
        $user = Auth::user();
        $enrolled = $user->validEnrolment($courses); //all math courses enrolled in
        $allreadyenrolled = $user->validEnrolment($allreadycourses); //allready enrolled

        if (!count($enrolled)) return response()->json(['message'=>'Not properly enrolled or first time user', 'code'=>203]);
        $house = \App\House::findOrFail($enrolled->last()->house_id);

        if (count($allreadyenrolled)>0) {                               // if enrolled in AllReady Math program
            $diagnostic = count($user->completedquizzes)<1 || $user->diagnostic ? TRUE : FALSE;
            $quiz_name = $diagnostic ? $house->house : $user->name;

            // if $diagnostic
            //    if housequiz exists
            //       if user assigned to an incomplete housequiz
            //             $quiz = latest housequiz assigned to user
            //       else $quiz = newest housequiz
            //    else create a new diagnostic house quiz
            // elseif user has incomplate quizzes
            //       $quiz = latest incomplete quiz
            //    else create a new personal quiz

             if ($diagnostic){
                if (count($house->valid_quizzes)) {
                    if (count($house->incomplete_housequiz($user))){
                       $quiz = $house->incomplete_housequiz($user)->last(); 
                    }
                else { 
                       $quiz = $house->valid_quizzes->diff($user->quizzes)->last(); 
                    }
                }
            } else {
                if (count($user->incompletequizzes) > 0) {
                    $quiz = $user->incompletequizzes()->first();
                }
             } 
            //if there's a house quiz and $diagnostic
            //   if there's incompleted quiz, $quiz = incomplete quiz
            //   else create new quiz
            //elseif user has inomplete house quizzes
            //      $quiz = not completed housequiz
            //    else $quiz= house quiz
/*            if (count($house->valid_quizzes) < 1){
                if (count($user->incompletequizzes) > 0){
                    $quiz = $user->incompletequizzes()->first();   
                }
            }
            elseif (count($house->incomplete_housequiz($user))>0){    //there are house quizzes incompleed or not attempted
                    $quiz = $house->incomplete_housequiz($user)->last();
                }
                else {
                    $quiz = $house->valid_quizzes->diff($user->quizzes)->last();
                }
*/
            $new_quiz = !$quiz ? $user->quizzes()->create(['quiz'=>$quiz_name."'s ".date("m/d/Y")." AllReady Quiz",'description'=> $quiz_name."'s ".date("m/d/Y")." AllReady Quiz", 'start_available_time'=> date('Y-m-d', strtotime('-1 day')), 'end_available_time'=>date('Y-m-d', strtotime('+1 month')),'diagnostic'=>$diagnostic]): $quiz;

            $diagnostic ? $new_quiz->houses()->sync([$house->id=>['start_date'=>date('Y-m-d', strtotime('-1 day')), 'end_date'=>date('Y-m-d', strtotime('+1 month'))]], false) : null; //assign house quiz

            $new_quiz->quizzees()->sync([$user->id], false);
            return $new_quiz->fieldQuestions($user, $house);                // output quiz questions
        }

        $test = count($user->currenttest)<1 ? !count($user->completedtests) || $user->diagnostic ? 
            $user->tests()->create(['test'=>$user->name."'s Diagnostic test",'description'=> $user->name."'s diagnostic test ".date('Y-m-d',strtotime('0 day')), 'start_available_time'=> date('Y-m-d', strtotime('-1 day')), 'end_available_time'=>date('Y-m-d', strtotime('+1 year')),'diagnostic'=>TRUE, 'level_id'=>2]):
            $user->tests()->create(['test'=>$user->name."'s ".date("m/d/Y")." test",'description'=> $user->name."'s ".date("m/d/Y")." Test", 'start_available_time'=> date('Y-m-d', strtotime('-1 day')), 'end_available_time'=>date('Y-m-d', strtotime('+1 year')),'diagnostic'=>FALSE]):
            $user->currenttest[0];
        return $test->fieldQuestions($user);                // output test questions
    }

    /**
     * Sends a list of questions of the test number to the front end
     *
     * One question from the highest skill of each track from the appropriate level
     *
     * @return \Illuminate\Http\Response
     */
    public function store(StoreMasterCodeRequest $request){
//        $courses = Course::where('course', 'LIKE', '%K to 6 Math%')->pluck('id');
        $user = Auth::user();
        $check_mastercode = $request->mastercode >0 ? Enrolment::whereMastercode($request->mastercode)->first():null;
        if (!$check_mastercode) return response()->json(['message'=>'Invalid credentials. Please contact us at math@allgifted.com if you have purchased product.', 'code'=>404], 404);
        $house_id = [$check_mastercode->house_id];
        if (count($user->validEnrolment($house_id))){
          return response()->json(['message'=>'Already enrolled in course', "code"=>404], 404);  
        }
        if ($check_mastercode->places_alloted) {
//            $date = new DateTime('now');            
            $houses = \App\House::find($check_mastercode->house_id);
            $mastercode = $check_mastercode->places_alloted < 1 ? null : $request->mastercode;
            $check_mastercode->places_alloted -= 1;
            $check_mastercode->save();
            $enrolment = Enrolment::firstOrNew(['user_id'=>$user->id, 'house_id'=>$check_mastercode->house_id, 'role_id'=>Role::where('role', 'LIKE', '%Student%')->first()->id]);
            $enrolment->fill(['start_date'=>new DateTime('now'),'expiry_date'=>(new DateTime('now'))->modify('+1 year'), 'payment_email'=>$check_mastercode->payment_email, 'purchaser_id'=>$check_mastercode->user_id, 'transaction_id'=>$check_mastercode->transaction_id, 'payment_status'=>$check_mastercode->payment_status, 'amount_paid'=>$check_mastercode->amount_paid, 'currency_code'=>$check_mastercode->currency_code])->save();
            $user->date_of_birth = Carbon::createFromFormat('m/d/Y', $request->date_of_birth)->format('Y-m-d');        
            $user->update(['firstname'=>$request->firstname, 'lastname'=>$request->lastname, 'date_of_birth'=>$user->date_of_birth]);
            $note = 'Dear '.$user->firstname.',<br><br>Thank you for enrolling in the '.$houses->description.' program!<br><br> You should be presented questions for the diagnosis test and we will start to monitor your progress from now.<br><br> You should check your progress periodically at http://math.all-gifted.com. <br><br>Should you have any queries, please do not hesitate to contact us at math@allgifted.com.<br><br>Thank you. <br><br> <i>This is an automated machine generated by the All Gifted System.</i>';

            Mail::send([],[], function ($message) use ($user,$note) {
                $message->from('info.allgifted@gmail.com', 'All Gifted Admin')
                        ->to($user->email)->cc('kang@allgifted.com')
                        ->subject('Successful Enrolment')
                        ->setBody($note, 'text/html');
            });            

        } else return response()->json(['message'=>'There is no more places left for the mastercode you keyed in.',  'code'=>404], 404);
        return $this->index();
    }

    /**
     * Checks answers and then sends a new set of questions, according to correctness of 
     * questions.  Checks the following
     *
     * @return \Illuminate\Http\Response
     */
    public function answer(CreateQuizAnswersRequest $request){
        $user = Auth::user();
        $house = \App\House::findOrFail($user->enrolledClasses()->latest()->first()->house_id);
        $quiz = $user->quizzes()->latest()->first();
        $test = $user->tests()->latest()->first();
        if (!$test && !$quiz){
            return response()->json(['message' => 'Invalid Test/Quiz', 'code'=>405], 405);    
        }

        foreach ($request->question_id as $key=>$question_id) {
            $question = Question::find($question_id);
            $answer = $request->answer;
            if (!$question){
                $user->errorlogs()->create(['error'=>'Question '.$question_id.' not found']);
                return response()->json(['message'=>'Error in question. No such question', 'code'=>403]);                
            }
            
            if ($request->test_id) {
                $assigned = $question->tests()->whereTestId($test->id)->first();
                if (!$assigned) {
                    $user->errorlogs()->create(['error'=>'Question '.$question_id.' not assigned to '. $user->name]);
                    return response()->json(['message'=>'Question not assigned to '. $user->name, 'code'=>403]);
                }                                
            }

            $correctness = $question->correctness($user, $answer[$key]);
            $answered = $question->answered($user, $correctness, $test, $quiz); // update question_user
            $track = $question->skill->tracks()->first(); // change logic, take the first track

            // calculate and saves maxile at 3 levels: skill, track and user            
            if ($quiz) {
                $skill_passed = $question->skill->handleQuiz($user, $question, $correctness);
            }

            if ($test) {
                $skill_maxile = $question->skill->handleAnswer($user->id, $question->difficulty_id, $correctness, $track, $test);
                $track_maxile = $track->calculateMaxile($user, $correctness, $test);
                $field_maxile = $user->storefieldmaxile($track_maxile, $track->field_id);

                // find the class
                if (!$test->diagnostic) {
                    $house = $track->houses->intersect(\App\House::whereIn('id', Enrolment:: whereUserId($user->id)->whereRoleId(6)->pluck('house_id'))->get())->first();
                    if ($house) {
                        $enrolment = Enrolment::whereUserId($user->id)->whereRoleId(6)->whereHouseId($house-> id)->first();
                        $enrolment['progress'] = round($user->tracksPassed->intersect(\App\House::find(1)->tracks)->avg('level_id')*100);
                        $enrolment->save();
                    }
                }
            }
        }

        return !$quiz ? $test->fieldQuestions($user): $quiz->fieldQuestions($user, $house);
    }
    /**
     * Enrolls a student 
     *
     * @return \Illuminate\Http\Response
     */
    public function mastercodeEnrol($request){
        return $request->all();
    }

    /**
     * Analyzes a student 
     *
     * @return \Illuminate\Http\Response
     */
    public function report($id){
        $logon_user = Auth::user();
        if ($logon_user->id && !$logon_user->is_admin) {
            return response()->json(['message' => 'You have no access rights to do a report','code'=>401], 401);
        }

        $user = User::findOrFail($id);
        $latest_test = $user->tests()->orderBy('start_available_time','desc')->first();

        $result = null;
        $questions_done = null;
        $note = null;

        if (count($user->answeredQuestion)<1) {
            $questions_done = "No question answered";
        } else {
            $correct_questions = $user->myQuestions()->whereCorrect(TRUE)->get();
            $incorrect_questions = $user->myQuestions()->whereCorrect(FALSE)->whereQuestionAnswered(TRUE)->get();
            if (count($incorrect_questions)<1) {
                $questions_done = "You didn't answer any question wrongly.";
            } else {
                $questions_done = "\x0DThese are the questions you have gotten wrong: \x0D";
                foreach ( $incorrect_questions as $question) {
                    $questions_done = $questions_done."\x0D".$question->id."\x09".$question->question."\x09Skill:".$question->skill->id."\x0D";                
                }
            }
            if (count($correct_questions)<1) {
                $questions_done = $questions_done."\x0DYou didn't answer any question correctly.";
            } else {
                $questions_done = $questions_done."\x0DThese are the questions you have gotten correct: \x0D";
                foreach ( $correct_questions as $question) {
                    $questions_done = $questions_done."\x0D".$question->id."\x09".$question->question."\x09Skill:".$question->skill->id."\x0D";                
                }
            }
        }
        $skillpassed = null; 
        $skillfailed = null;
        if (count($user->skill_user)<1) {
            $skillpassed = "No skill passed";
            $skillfailed = "No skill failed";
        } else {
            foreach ($user->skill_user as $skill){
                if ($skill->pivot->skill_passed) {
                    $skillpassed = $skillpassed."\x0DSkill: '".$skill->skill."' of Level:".$skill->tracks()->first()->level->description;
                } else {
                    $skillfailed = count($skill->tracks)>0?$skillfailed."\x0DSkill: '".$skill->skill."' of Level:".$skill->tracks()->first()->level->description: $skillfailed;
                }
            }            
        }
        //maxile
        $next_level=Level::whereStartMaxileLevel((int)($user->maxile_level/100)*100)->first();
        
        $new_maxile = $latest_test ? $user->calculateUserMaxile($latest_test) : 0;

        //tests
        if (count($user->tests)<1) {
            if (!count($user->quizzes)) $note="No test/quiz administered";      
            else {
                $diagnostic_status = !$user->quizzes()->first()->pivot->completed_date ? "not completed." : "completed on ".$user->quizzes()->first()->pivot->completed_date; 
                    $note = "Dear ".$user->name.",\x0D\x0DYou first enrolled on ".$user->enrolment()->first()->start_date.". Your diagnostic quiz was administered on ".$user->quizzes()->first()->pivot->created_at." and was ".$diagnostic_status;
                    foreach ($user->quizzes as $quiz) {
                        $result = $quiz->pivot->completed_date ? $result. "\x0DQuiz: ".$quiz->description.' (Diagnostic: '.$quiz->diagnostic.')  Result:'.$quiz->pivot->result."%.":$result."\x0DTest:".$quiz->description.":  Did not complete quiz.";
                        foreach ($quiz->skills as $quizskill){
                            $total_attempted = \App\QuestionQuizUser::whereUserId($user->id)->whereQuizId($quiz->id)->whereIn('question_id',\App\Question::whereSkillId($quizskill->id)->pluck('id'))->count();
                            $total_correct = \App\QuestionQuizUser::whereUserId($user->id)->whereQuizId($quiz->id)->whereIn('question_id',\App\Question::whereSkillId($quizskill->id)->pluck('id'))->whereCorrect(TRUE)->count();
                            $percent = $total_attempted ? $total_correct/$total_attempted *100 : 0;
                            $result = $result."\x0DSkill: ".$quizskill->description. ' | Achievement: '.round($percent,2).'%';
                        }
                    }
            }
        } else {
            $diagnostic_status = !$user->tests()->first()->pivot->completed_date ? "not completed." : "completed on ".$user->tests()->first()->pivot->completed_date; 
 
            $note = "Dear ".$user->name.",\x0D\x0DYou first enrolled on ".$user->enrolment()->first()->start_date.". Your diagnostic test was administered on ".$user->tests()->first()->pivot->created_at." and was ".$diagnostic_status.".";
            $note = count($user->tests) > 1 ? $note."\x0D\x0DYou did a total of another ".(count($user->tests)-1)." tests" : $note;
            foreach ($user->tests as $test) {
                $result = $test->pivot->completed_date ? $result. "\x0DTest: ".$test->description.'  Result:'.$test->pivot->result."%.":$result."\x0DTest:".$test->description.":  Did not complete test.";   
            }

        }
        $note = $note."\x0D\x0DYour results are: \x0D".$result.

            "\x0D\x0DIn total, you have answered ".count($user->answeredQuestion)." questions. Out of which you obtained ".$user->myQuestions()->sum('correct')." of them correct.".$questions_done.
            "\x0DThe skills you passed are: ".$skillpassed."\x0D\x0DThe skills you attempted and did not pass are:".$skillfailed.
            "\x0D\x0DAs such, your maxile level is now at ".$user->maxile_level.".";
        Mail::send([],[], function ($message) use ($user,$note) {
            $message->from("info.allgfited@gmail.com", 'All Gifted Admin')
                    ->to('math@allgifted.com','jo@allgifted.com', 'kang@allgifted.com')
                    ->subject($user->name."'s report")
                    ->setBody($note, 'text/html');
        });

        return response()->json(['message' => $note, 'code'=>201], 201);                        
    }

}
