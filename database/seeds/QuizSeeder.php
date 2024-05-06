<?php

use Illuminate\Database\Seeder;
use Faker\Factory as Faker;

class QuizSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        $faker = Faker::create();

//        DB::table('quizzes')->truncate();

        for ($id = 0; $id < 10; $id++) {
            $quizId = DB::table('quizzes')->insertGetId([
                'quiz' => $faker->name,
                'description' => $faker->sentence,
                'user_id' => 2,
                'diagnostic' => 1,
                'image' => $faker->imageUrl(),
                'start_available_time' => date('Y-m-d H:i:s', time()),
                'end_available_time' => date('Y-m-d H:i:s', time()),
                'due_time' => date('Y-m-d H:i:s', time()),
                // 'number_of_tries_allowed' => 0,
                // 'which_result' => $faker->sentence,
                'status_id' => 1,
            ]);

            $courseId = DB::table('courses')->insertGetId([
                'course' => $faker->name,
                'description' => $faker->sentence,
                'start_maxile_score' => 2,
                'end_maxile_score' => 1,
                'user_id' => 2,
                'image' => $faker->imageUrl(),
                'status_id' => 1,
                'prereq_course_id' => 1,
            ]);

            $houseId = DB::table('houses')->insertGetId([
                'house' => $faker->name,
                'description' => $faker->sentence,
                'user_id' => 2,
                'course_id' => $courseId,
                'image' => $faker->imageUrl(),
                'status_id' => 1,
                // 'price' => 1,
                'start_date' => date('Y-m-d', time()),
                'end_date' => date('Y-m-d', time()),
                // 'currency' => $faker->name,
                // 'underperform' => 1,
                // 'overperform' => 1,
                'framework_id' => 1,
                // 'start_framework' => 1,
                // 'end_framework' => 1,
            ]);

            DB::table('house_quiz')->insertGetId([
                'house_id' => $houseId,
                'quiz_id' => $quizId,
                // 'start_date' => date('Y-m-d', time()),
                // 'end_date' => date('Y-m-d', time()),
                // 'result' => 1,
                // 'attempts' => 0,
                // 'which_attempt' => 0,
            ]);

            $skillId = DB::table('skills')->insertGetId([
                'skill' => $faker->name,
                'description' => $faker->sentence,
                'user_id' => 2,
                'image' => $faker->imageUrl(),
                'lesson_link' => $faker->imageUrl(),
                'status_id' => 1,
                // 'check' => 1,
                'track_id' => 1,
            ]);

            $diffId = DB::table('difficulties')->insertGetId([
                'difficulty' => 1,
                'short_description' => 'short_description',
                'description' => $faker->sentence,
                'user_id' => 2,
                'image' => $faker->imageUrl(),
                'status_id' => 1,
            ]);

            $typeId = DB::table('types')->insertGetId([
                'type' => 'type name',
                'description' => $faker->sentence,
            ]);


            $questionId = DB::table('questions')->insertGetId([
                'skill_id' => $skillId,
                'difficulty_id' => $diffId,
                'user_id' => 2,
                'question' => $faker->sentence,
                'question_image' => $faker->imageUrl(),
                'correct_answer' => $faker->imageUrl(),
                'status_id' => 1,
                'type_id' => $typeId,
            ]);

            DB::table('question_quiz')->insertGetId([
                'question_id' => $questionId,
                'quiz_id' => $quizId,
                // 'correct' => 1,
                // 'date_answered' => date('Y-m-d', time()),
            ]);


            DB::table('quiz_skill')->insertGetId([
                'quiz_id' => $quizId,
                'skill_id' => $skillId,
            ]);



        }
    }
}
