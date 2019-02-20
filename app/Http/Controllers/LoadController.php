<?php

/*namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Http\Requests;
use App\Http\Controllers\Controller;
use Maatwebsite\Excel\Facades\Excel;
use App\House_Skill;
use Auth;

class LoadController extends Controller
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
        ini_set('max_execution_time', 300);
        $currentuser =  \App\User::whereId(1)->first();
        Auth::login($currentuser);
        $this->loadusers();
        $this->loadlevels();
        $this->loadcourses();
        $this->loadtracks();
        $this->loadskills();
        $this->loadhouses();
        $this->loadroles();
        $this->loadquestions();
//        $this->loadmastercodes();
//        $this->loadtests();
        return "all uploaded";
    }

    public function loadusers(){
        Excel::selectSheets('users')->load('public/capstone_questions.xlsx', function ($reader) {
            $users = $reader->all();
            foreach ($users as $user) {
                \App\User::create($user->toArray());
            }
        });    
    }

    public function loadquestions ()
    {
        Excel::selectSheets('imp1')->load('public/capstone_questions.xlsx', function ($reader) {
            $questions = $reader->all();
            foreach ($questions as $question) {
                \App\Question::create($question->toArray());
            }
        });

        Excel::selectSheets('imp2')->load('public/capstone_questions.xlsx', function ($reader) {
            $questions = $reader->all();
            foreach ($questions as $question) {
                \App\Question::create($question->toArray());
            }
        });
    }
    /**
     * Show the form for creating a new resource.
     *
     * @return database is loaded with tracks
     */
    public function loadtracks()
    {
    /*    Excel::selectSheets('tracks')->load('public/capstone_questions.xlsx', function ($reader) {
            $tracks = $reader->all();
            foreach ($tracks as $track) {
                \App\Track::create($track->toArray());
            }
        });

        Excel::selectSheets('course_track')->load('public/capstone_questions.xlsx', function ($reader) {
            $course_tracks = $reader->all();
            foreach ($course_tracks as $track) {
                \App\Course_Track::create($track->toArray());
            }
        });

        Excel::selectSheets('track_track')->load('public/capstone_questions.xlsx', function ($reader) {
            $track_tracks = $reader->all();
            foreach ($track_tracks as $track) {
                \App\Track_Track::create($track->toArray());
            }
        });

        Excel::selectSheets('track_user')->load('public/capstone_questions.xlsx', function ($reader) {
            $track_users = $reader->all();
            foreach ($track_users as $track) {
                \App\TrackUser::create($track->toArray());
            }
        });

//        Excel::selectSheets('field_user')->load('public/capstone_questions.xlsx', function ($reader) {
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
    public function loadlevels()
    {
        Excel::selectSheets('levels')->load('public/capstone_questions.xlsx', function ($reader) {
            $levels = $reader->all();
            foreach ($levels as $level) {
                \App\Level::create($level->toArray());
            }
        });
    }

    /**
     * Store courses loaded from excel into the databast
     *
     * @param excel spread sheet
     * @return
     */
   /* public function loadcourses()
    {
        Excel::selectSheets('courses')->load('public/capstone_questions.xlsx', function ($reader) {
            $courses = $reader->all();
            foreach ($courses as $course) {
                \App\Course::create($course->toArray());
            }
        });
    }

    /**
     * Store levels loaded from excel into the databast
     *
     * @param excel spread sheet
     * @return
     */

/*    public function loadskills()
    {
        Excel::selectSheets('skills')->load('public/capstone_questions.xlsx', function ($reader) {
            $skills = $reader->all();
            foreach ($skills as $skill) {
                \App\Skill::create($skill->toArray());
            }
        });

        Excel::selectSheets('skill_track')->load('public/capstone_questions.xlsx', function ($reader) {
            $skill_tracks = $reader->all();
            foreach ($skill_tracks as $skill_track) {
                \App\Skill_Track::create($skill_track->toArray());
            }
        });

        Excel::selectSheets('skill_user')->load('public/capstone_questions.xlsx', function ($reader) {
            $skill_users = $reader->all();
            foreach ($skill_users as $skill_user) {
                \App\SkillUser::create($skill_user->toArray());
            }
        });
    }

    /**
     * Store roles loaded from excel into the databast
     *
     * @param excel spread sheet
     * @return
     */

  /*  public function loadroles ()
    {
        Excel::selectSheets('role_user')->load('public/capstone_questions.xlsx', function ($reader) {
            $house_role_users = $reader->all();
            foreach ($house_role_users as $house_role_user) {
                \App\Enrolment::create($house_role_user->toArray());
            }
        });

        Excel::selectSheets('permission_role')->load('public/capstone_questions.xlsx', function ($reader) {
            $permission_roles = $reader->all();
            foreach ($permission_roles as $permission_role) {
                \App\Permission_Role::create($permission_role->toArray());
            }
        });
    }

   public function loadhouses ()
    {
        Excel::selectSheets('houses')->load('public/capstone_questions.xlsx', function ($reader) {
            $houses = $reader->setDateFormat('Y-m-d')->all();
            foreach ($houses as $house) {
                \App\House::create($house->toArray());
            }
        });

        Excel::selectSheets('house_track')->load('public/capstone_questions.xlsx', function ($reader) {
            $house_tracks = $reader->setDateFormat('Y-m-d')->all();
            foreach ($house_tracks as $house_track) {
                \App\House_Track::create($house_track->toArray());
            }
        });
    }

    /**
     * Store mastercodes loaded from excel into the database
     *
     * @param excel spread sheet
     * @return
     */

/*    public function loadmastercodes ()
    {
        Excel::selectSheets('mastercodes')->load('public/capstone_questions.xlsx', function ($reader) {
            $mastercodes = $reader->all();
            foreach ($mastercodes as $mastercode) {
                \App\Mastercode::create($mastercode->toArray());
            }
        });
    }

}