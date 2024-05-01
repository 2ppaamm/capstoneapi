<?php

namespace App\Http\Controllers;

use App\House;
use App\HouseQuiz;
use App\Question;
use App\QuestionQuiz;
use App\Quiz;
use App\QuizQuestionUser;
use App\QuizSkill;
use App\Skill;
use App\Status;
use Illuminate\Http\Request;
use Auth;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;

class QuizController extends Controller
{
    /*
     * get all quizzess
     */
    public function index()
    {
        $user = Auth::user();

        if (!$user->is_admin) {
            return response()->json(['message' => 'Only administrators can access this information', 'code' => 403], 403);
        }

        return Quiz::with(['houses','skills', 'questions', 'user','quizzees'])->paginate(20);;
    }

    /**
     * Store a quiz.
     *
     * @param  \Illuminate\Http\Request $request
     */
    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'quiz' => 'required',
            'description' => 'required',
            'start_available_time' => 'date_format:"Y-m-d H:i:s"|required',
            'end_available_time' => 'date_format:"Y-m-d H:i:s"|required',
        ]);

        if ($validator->fails()) {
            return response()->json(['message' => 'Create quiz failed', 'data' => $validator->errors(), 'code' => 201]);
        }

        $quiz = Quiz::create($request->except(['houses', 'skills']));
        // add houses
        if ($request->exists('houses')) {
            $houses = array_wrap($request->get('houses'));
            $quiz->houses()->sync($houses, false);
        }

        // add skills
        if ($request->exists('skills')) {
            $skills = array_wrap($request->get('skills'));
            $quiz->skills()->sync($skills,false);
            $skillIds = array_column($quiz->skills, 'id'); // Extract the skill IDs from the array
            $questions = Questions::whereIn('skill_id', $skillIds)->take(50)->get();

/*            foreach ($skills as $skill) {
                // add questions
                $questionId = Question::where('skill_id', $skill)->whereSource('diagnostic')->value('id');
                if ($questionId) {
                    $quiz->questions()->sync($questionId, false);
                }

            }*/
        }

        return $quiz->with('skills','houses')->get();
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  \Illuminate\Http\Request $request
     * @param  Quiz $quiz
     * @return \Illuminate\Http\Response
     */
    public function show(Quiz $quiz)
    {
    /**
     * Update the specified resource in storage.
     *
     * @param  \Illuminate\Http\Request $request
     * @param  Quiz $quiz
     * @return \Illuminate\Http\Response
     */
        return $quiz;
    }

    public function update(Request $request, Quiz $quiz)
    {
        $logon_user = Auth::user();
        if (!$logon_user->is_admin || $login_user->id == $quiz->user->id) {
            return response()->json(['message' => 'You have no access rights to update skill', 'code' => 401], 401);
        }

        $quiz->fill($request->except(['houses', 'skills']))->save();
        $quiz->skills()->sync($request->skills, true);
        $quiz->houses()->sync($request->houses, true);

        return response()->json(['message'=>'Quiz updated','quiz' => $quiz, 'code'=>201], 201);
    }

    /**
     * Remove the specified quiz.
     */
    public function destroy(Request $request, Quiz $quiz)
    {

        $deLink = $request->exists('delink_all') ? $request->get('delink_all') : false;

        if (($quiz->questions()->count() || $quiz->houses()->count()) && !$deLink) {
            return response()->json(['message' => 'There are classes or questions linked to this quiz, do you want to delink them first before deleting the quiz?', 'code' => 409], 409);
        }

        $quiz->questions()->detach();
        $quiz->skills()->detach();
        $quiz->houses()->detach();
        $quiz->delete();

        return response()->json(['message' => 'Quiz has been successfully deleted', 'code' => 201], 201);
    }

    /**
     * copy the specified quiz.
     */
    public function copy(Request $request, Quiz $quiz)
    {
        $questions = $quiz->questions()->pluck('id')->toArray();
        $skills = $quiz->skills()->pluck('id')->toArray();
        $houses = $quiz->houses()->pluck('id')->toArray();

//        unset($quiz['id'], $quiz['questions'], $quiz['skills'], $quiz['houses']);

//        $quiz['quiz'] = 'copy' . $quiz['quiz'];

        $newQuiz = Quiz::create(array_diff($quiz->toArray(), $quiz->pluck('id')->toArray()));

        $newQuiz->questions()->sync($questions, false);
        $newQuiz->skills()->sync($skills, false);
        $newQuiz->houses()->sync($houses, false);

        return $newQuiz;
    }

    public function create()
    {
        $houses = Auth::user()->is_admin ? House::select('id','house','description')->get() : House::whereUserId(Auth::user()->id)->select('id','house','description')->get();  
        return ['statuses' => Status::select('id','status','description')->get(), 'skills' => Skill::select('id','skill','description')->get(), 'houses'=>$houses];
    }
}