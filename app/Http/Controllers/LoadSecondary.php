<?php

/*namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Http\Requests;
use App\Http\Controllers\Controller;
use Maatwebsite\Excel\Facades\Excel;
use App\House_Skill;
use Auth;

class LoadSecondary extends Controller
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
        $this->loadtracks();
        $this->loadskills();
        $this->loadquestions();
//        $this->loadsolutions();
        return "secondary uploaded ok";
    }


    /**
     * Show the form for creating a new resource.
     *
     * @return database is loaded with tracks
     */
/*    public function loadtracks()
    {
        Excel::selectSheets('tracks')->load('public/sec_questions.xlsx', function ($reader) {
            $tracks = $reader->all();
            foreach ($tracks as $track) {
                \App\Track::create($track->toArray());
            }
        });

//        Excel::selectSheets('course_track')->load('public/sec_questions.xlsx', function ($reader) {
  //          $course_tracks = $reader->all();
    //        foreach ($course_tracks as $track) {
      //          \App\Course_Track::create($track->toArray());
        //    }
       // });

//        Excel::selectSheets('track_track')->load('public/sec_questions.xlsx', function ($reader) {
  //          $track_tracks = $reader->all();
    //        foreach ($track_tracks as $track) {
      //          \App\Track_Track::create($track->toArray());
   //         }
   //     });

//        Excel::selectSheets('track_user')->load('public/sec_questions.xlsx', function ($reader) {
  //          $track_users = $reader->all();
    //        foreach ($track_users as $track_user) {
      //          \App\TrackUser::create($track_user->toArray());
        //    }
     //   });

//        Excel::selectSheets('field_user')->load('public/sec_questions.xlsx', function ($reader) {
  //          $field_users = $reader->all();
    //        foreach ($field_users as $field_user) {
      //          \App\FieldUser::create($field_user->toArray());
        //    }
    //    });
    }

    /**
     * Store levels loaded from excel into the databast
     *
     * @param excel spread sheet
     * @return
     */

  /*  public function loadskills()
    {
        Excel::selectSheets('skills')->load('public/sec_questions.xlsx', function ($reader) {
            $skills = $reader->all();
            foreach ($skills as $skill) {
                \App\Skill::create($skill->toArray());
            }
        });

        Excel::selectSheets('skill_track')->load('public/sec_questions.xlsx', function ($reader) {
            $skill_tracks = $reader->all();
            foreach ($skill_tracks as $skill_track) {
                \App\Skill_Track::create($skill_track->toArray());
            }
        });
    }

    public function loadquestions ()
    {
        Excel::selectSheets('imp1')->load('public/sec_questions.xlsx', function ($reader) {
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
        Excel::selectSheets('solutions')->load('public/sec_questions.xlsx', function ($reader) {
            $solutions = $reader->all();
            foreach ($solutions as $solution) {
                \App\Solution::create($solution->toArray());
            }
        });
    }
}