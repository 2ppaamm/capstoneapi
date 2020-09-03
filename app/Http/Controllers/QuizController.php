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

        $quizzess = [];

        foreach (Quiz::all() as $data) {
            $quiz = [
                'quiz' => $data,
                'properties' => [
                    'houses' => [],
                    'questions' => [],
                    'skills' => [],
                ]
            ];

            $houseIds = HouseQuiz::where('quiz_id', $data->id)->pluck('house_id')->toArray();
            if ($houseIds) {
                $houses = House::whereIn('id', $houseIds)->get();
                if ($houses) {
                    $quiz['properties']['houses'] = $houses;
                }
            }

            $questionIds = QuestionQuiz::where('quiz_id', $data->id)->pluck('question_id')->toArray();
            if ($questionIds) {
                $questions = Question::whereIn('id', $questionIds)->get();
                if ($questions) {
                    $quiz['properties']['questions'] = $questions;
                }
            }

            $skillIds = QuizSkill::where('quiz_id', $data->id)->pluck('skill_id')->toArray();
            if ($skillIds) {
                $skills = Skill::whereIn('id', $skillIds)->get();
                if ($skills) {
                    $quiz['properties']['skills'] = $skills;
                }
            }

            $quizzess[] = $quiz;
            continue;
        }

        return $quizzess;
    }

    /**
     * Store a quiz.
     *
     * @param  \Illuminate\Http\Request $request
     */
    public function store(Request $request)
    {
//        quiz (name of quiz)*
//description*
//start_available_time*
//end_available_time*
//due_time
//no_of_tries_allowed
//houses
//skills

//        if houses are present create a row in house_quiz
//If an array of skills are present, add a row in quiz_skill, then find all the questions in those skills and link them all to the quiz in question_quiz.


        $validator = Validator::make($request->all(), [
            'quiz' => 'required',
            'description' => 'required',
            'start_available_time' => 'date_format:"Y-m-d H:i:s"|required',
            'end_available_time' => 'date_format:"Y-m-d H:i:s"|required',
        ]);

        if ($validator->fails()) {
            return response()->json(['message' => 'create quiz is failed', 'data' => $validator->errors(), 'code' => 201]);
        }

        $quiz = Quiz::create($request->except(['houses', 'skills']));

        // add houses
        if ($request->exists('houses')) {
            $houses = array_wrap($request->get('houses'));
            foreach ($houses as $house) {
                HouseQuiz::create(array_merge($house, ['quiz_id' => $quiz->id]));
            }
        }

        // add skills
        if ($request->exists('skills')) {
            $skills = array_wrap($request->get('skills'));
            foreach ($skills as $skill) {
                QuizSkill::create(array_merge($skill, ['quiz_id' => $quiz->id]));
                // add questions
                $skillId = $skill['skill_id'];
                $questionId = Question::where('skill_id', $skillId)->value('id');
                if ($questionId) {
                    QuestionQuiz::create(['quiz_id' => $quiz->id, 'question_id' => $questionId]);
                }

            }
        }


        $quizzess = [];

        $id = $quiz->id;

        $data = Quiz::find($id);
        $quiz = [
            'quiz' => $data,
            'properties' => [
                'houses' => [],
                'questions' => [],
                'skills' => [],
            ]
        ];

        $houseIds = HouseQuiz::where('quiz_id', $data->id)->pluck('house_id')->toArray();
        if ($houseIds) {
            $houses = House::whereIn('id', $houseIds)->get();
            if ($houses) {
                $quiz['properties']['houses'] = $houses;
            }
        }

        $questionIds = QuestionQuiz::where('quiz_id', $data->id)->pluck('question_id')->toArray();
        if ($questionIds) {
            $questions = Question::whereIn('id', $questionIds)->get();
            if ($questions) {
                $quiz['properties']['questions'] = $questions;
            }
        }

        $skillIds = QuizSkill::where('quiz_id', $data->id)->pluck('skill_id')->toArray();
        if ($skillIds) {
            $skills = Skill::whereIn('id', $skillIds)->get();
            if ($skills) {
                $quiz['properties']['skills'] = $skills;
            }
        }

        $quizzess[] = $quiz;
        return $quizzess;
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  \Illuminate\Http\Request $request
     * @param  Quiz $quiz
     * @return \Illuminate\Http\Response
     */
    public function update(Request $request, Quiz $quiz)
    {
        $logon_user = Auth::user();
        if (!$logon_user->is_admin) {
            return response()->json(['message' => 'You have no access rights to update skill', 'code' => 401], 401);
        }

        dd($quiz);

//        if ($request->hasFile('image')) {
//            if (file_exists($course->image)) unlink($course->image);
//            $timestamp = time();
//            $course->image = 'images/courses/' . $timestamp . '.png';
//
//            $file = $request->image->move(public_path('images/courses'), $timestamp . '.png');
//        }
//
//        $course->fill($request->except('image'))->save();
//
//        return response()->json(['message' => 'Course updated', 'course' => $course, 201], 201);
    }
}
