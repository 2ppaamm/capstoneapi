<?php

/*namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Http\Requests;
use App\Http\Controllers\Controller;
use Maatwebsite\Excel\Facades\Excel;
use App\House_Skill;
use Auth;

class LoadQuestions extends Controller
{
   public function __construct(){
       $this->middleware('cors');
   }
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
  /*  public function loadall()
    {
        $currentuser =  \App\User::whereId(3)->first();
        Auth::login($currentuser);
        $this->loadquestions();
//        $this->loadsolutions();
        return "questions uploaded ok";
    }


    public function loadquestions ()
    {
        Excel::selectSheets('imp3')->load('public/questions.xlsx', function ($reader) {
            $questions = $reader->all();
            foreach ($questions as $question) {
                \App\Question::create($question->toArray());
            }
        });
        Excel::selectSheets('imp4')->load('public/questions.xlsx', function ($reader) {
            $questions = $reader->all();
            foreach ($questions as $question) {
                \App\Question::create($question->toArray());
            }
        });
        Excel::selectSheets('imp5')->load('public/questions.xlsx', function ($reader) {
            $questions = $reader->all();
            foreach ($questions as $question) {
                \App\Question::create($question->toArray());
            }
        });
        Excel::selectSheets('imp6')->load('public/questions.xlsx', function ($reader) {
            $questions = $reader->all();
            foreach ($questions as $question) {
                \App\Question::create($question->toArray());
            }
        });
    }

    /**
     * Store levels loaded from excel into the databast
     *
     * @param excel spread sheet
     * @return
     */

/*    public function loadsolutions ()
    {
        Excel::selectSheets('solutions')->load('public/questions.xlsx', function ($reader) {
            $solutions = $reader->all();
            foreach ($solutions as $solution) {
                \App\Solution::create($solution->toArray());
            }
        });
    }
}