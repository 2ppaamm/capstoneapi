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
            return response()->json(['message' => 'Only administrators can access this api', 'code' => 403], 403);
        }

        return Quiz::with(['houses','questions'])->get();
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
            foreach ($skills as $skill) {
                // add questions
                $questionId = Question::where('skill_id', $skill)->whereSource('diagnostic')->value('id');
                if ($questionId) {
                    $quiz->questions()->sync($questionId, false);
                }

            }
        }

        return $quiz->with('questions','houses')->get();
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
        if (!$logon_user->is_admin) {
            return response()->json(['message' => 'You have no access rights to update skill', 'code' => 401], 401);
        }

        if ($request->exists('add_skills')) {
            $quiz->skills()->sync($request->add_skills, false);
            foreach ($request->get('add_skills') as $skill) {

                // store question in question_quiz table
                $questionId = Question::where('skill_id', $skill)->value('id');
                if (!$questionId) {
                    continue;
                }
                $quiz->questions()->sync([$questionId], false);
            }
        }

        if ($request->exists('remove_skills')) {
            foreach ($request->get('remove_skills') as $skill) {

                $skillId = $skill['skill_id'];
                QuizSkill::where(['quiz_id' => $quiz->id, 'skill_id' => $skillId])->delete();

                $questionId = Question::where('skill_id', $skillId)->value('id');
                if (!$questionId) {
                    continue;
                }
                QuestionQuiz::where(['quiz_id' => $quiz->id, 'question_id' => $questionId])->delete();
            }
        }


        if ($request->exists('add_houses')) {
            foreach ($request->get('add_houses') as $house) {
                $houseId = $house['house_id'];
                if (!House::where('id', $houseId)->count()) {
                    continue;
                }

                if (HouseQuiz::where(['quiz_id' => $quiz->id, 'house_id' => $houseId])->count()) {
                    continue;
                }
                HouseQuiz::create(array_merge(['quiz_id' => $quiz->id], $house));
            }
        }

        if ($request->exists('remove_houses')) {
            foreach ($request->get('remove_houses') as $house) {
                $houseId = $house['house_id'];
                HouseQuiz::where(['quiz_id' => $quiz->id, 'house_id' => $houseId])->delete();
            }
        }

        if ($request->exists('add_questions')) {
            foreach ($request->get('add_questions') as $question) {
                $questionId = $question['question_id'];

                if (!Question::where('id', $questionId)->count()) {
                    continue;
                }
                if (QuestionQuiz::where(['quiz_id' => $quiz->id, 'question_id' => $questionId])->count()) {
                    continue;
                }
                QuestionQuiz::create(array_merge(['quiz_id' => $quiz->id], $question));
            }
        }

        if ($request->exists('remove_questions')) {
            foreach ($request->get('remove_questions') as $question) {
                $questionId = $question['question_id'];
                QuestionQuiz::where(['quiz_id' => $quiz->id, 'question_id' => $questionId])->delete();
            }
        }

        $quiz->fill($request->except(['houses', 'skills']))->save();

        return $quiz;
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
        return ['statuses' => Status::select('id','status','description')->get(), 'skills' => Skill::select('id','skill','description')->get(), 'houses'=>House::select('id','house','description')->get()];
    }
}