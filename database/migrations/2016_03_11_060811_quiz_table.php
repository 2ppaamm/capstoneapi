<?php

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class QuizTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('quizzes', function (Blueprint $table) {
            $table->increments('id');
            $table->string('quiz');
            $table->string('description');
            $table->integer('user_id')->unsigned()->default(2);
            $table->foreign('user_id')->references('id')->on('users')->ondelete('cascade');
            $table->timestamps();
        });





        Schema::create('question_quiz', function (Blueprint $table) {
            $table->integer('quiz_id')->unsigned();
            $table->foreign('quiz_id')->references('id')->on('quizzes');
            $table->integer('question_id')->unsigned()->default(1);
            $table->foreign('question_id')->references('id')->on('questions');
            $table->date('date_answered');
            $table->boolean('correct');
            $table->primary(['quiz_id','question_id']);
            $table->timestamps();
        });

        Schema::create('house_quiz', function (Blueprint $table) {
            $table->integer('house_id')->unsigned();
            $table->foreign('house_id')->references('id')->on('quizzes');
            $table->integer('quiz_id')->unsigned()->default(1);
            $table->foreign('quiz_id')->references('id')->on('users');
            $table->date('start_date');
            $table->date('end_date');
            $table->decimal('result', 3,2);
            $table->integer('attempts');
            $table->integer('which_attempt');
            $table->primary(['house_id','quiz_id']);
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::drop('house_quiz');
        Schema::drop('question_quiz');        
        Schema::drop('quizzes');
    }
}
