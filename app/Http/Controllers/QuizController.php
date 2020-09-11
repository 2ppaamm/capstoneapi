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

        $quizzess = [];

        foreach (Quiz::orderBy('id', 'desc')->get() as $quiz) {
            $quizzess[] = $this->getQuiz($quiz->id);
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

        $id = $quiz->id;
        return $this->getQuiz($id);
    }

    protected function getQuiz($id)
    {
        $quiz = Quiz::find($id);
        $data = array_merge($quiz->toArray(), [
            'houses' => [],
            'questions' => [],
            'skills' => [],
        ]);

        $houseIds = $quiz->houses->pluck('house_id');

        if ($houseIds) {
            $houses = House::whereIn('id', $houseIds)->get();
            if ($houses) {
                $data['houses'] = $houses;
            }
        }

        $questionIds = $quiz->questions->pluck('question_id');
        if ($questionIds) {
            $questions = Question::whereIn('id', $questionIds)->get();
            if ($questions) {
                $data['questions'] = $questions;
            }
        }

        $skillIds = $quiz->skills->pluck('skill_id');
        if ($skillIds) {
            $skills = Skill::whereIn('id', $skillIds)->get();
            if ($skills) {
                $data['skills'] = $skills;
            }
        }

        return $data;
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
            foreach ($request->get('add_skills') as $skill) {
                $skillId = $skill['skill_id'];

                if (!Skill::where('id', $skillId)->count()) {
                    continue;
                }

                if (QuizSkill::where(['quiz_id' => $quiz->id, 'skill_id' => $skillId])->count()) {
                    continue;
                }

                // store quiz_skill table
                QuizSkill::create(['quiz_id' => $quiz->id, 'skill_id' => $skillId]);

                // store question in question_quiz table
                $questionId = Question::where('skill_id', $skillId)->value('id');
                if (!$questionId) {
                    continue;
                }
                QuestionQuiz::create(['quiz_id' => $quiz->id, 'question_id' => $questionId]);
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

        return $this->getQuiz($quiz->id);
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

        $quiz->questions()->delete();
        $quiz->skills()->delete();
        $quiz->houses()->delete();
        $quiz->delete();

        return response()->json(['message' => 'Quiz has been successfully deleted', 'code' => 201], 201);
    }

    /**
     * copy the specified quiz.
     */
    public function copy(Request $request, Quiz $quiz)
    {
        $questions = $quiz->questions;
        $skills = $quiz->skills;
        $houses = $quiz->houses;

        unset($quiz['id'], $quiz['questions'], $quiz['skills'], $quiz['houses']);

        $quiz['quiz'] = 'copy' . $quiz['quiz'];
        $newQuiz = Quiz::create($quiz->toArray());

        foreach ($questions as $question) {
            $question['quiz_id'] = $newQuiz->id;
            QuestionQuiz::create($question->toArray());
        }

        foreach ($skills as $skill) {
            $skill['quiz_id'] = $newQuiz->id;
            QuizSkill::create($skill->toArray());
        }

        foreach ($houses as $house) {
            $house['quiz_id'] = $newQuiz->id;
            HouseQuiz::create($house->toArray());
        }

        return $this->getQuiz($newQuiz->id);
    }

    public function create()
    {
        return ['statuses' => Status::select('id','status','description')->get(), 'skills' => Skill::select('id','skill','description')->get()];
    }

    public function fieldQuestions(Quiz $quiz)
    {

        $user = Auth::user();
        $fieldQuestions = $quiz->fieldQuestions($user);
        return response()->json(['message' => 'Questions fetched', 'quiz'=>$quiz->id, 'questions'=>$fieldQuestions, 'code'=>201]);



        /* Finding the 5 questions to return:
              * 2. If no question in !question_quiz_user->attempts for this quiz,
              *    a. if $quiz_user->completed, return error, 500.
              *    b. If $quiz->diagnostic, find $questions with skill_id in tracks in $user->enrolledClasses
              *       with $question->source = "diagnostic".
              *    c. If quiz is not diagnostic, and $questions<10, where $question->source != "diagnostic" and in
              *       this priority:
              *      i. Questions either not present in $question_quiz_user or !$question_quiz_user->correct
              *         (previous quizzes) that have skill_id belonging to a track with valid date: today between
              *         $house_track->start_date and end_date
              *      ii. if count($questions)<10 after (a), then find questions with skill_id in track where
              *          $housetrack->end_date < today, $user_skill->skill_passed & !$question_quiz_user->correct
              *      iii. if count($questions)<10 after (b), then find any questions with skill_id in track
              *          where $housetrack->end_date < today and in skill where !$user_skill->skill_passed
              *    d. When count($questions)>=10:
              *       i. $questions->take(10)
              *       ii. fill user_skill, user_track and question_quiz_user with the related skill, track, quiz
              *           and question information.
              *  2. if count($questions)>5 return $questions->take(5) else return $questions to front end
              *
              */


    }
}