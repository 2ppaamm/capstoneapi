<?php

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class QuestionTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('questions', function (Blueprint $table) {
            $table->increments('id');
            $table->integer('skill_id')->unsigned()->nullable();
            $table->foreign('skill_id')->references('id')->on('skills')->onDelete('set null');
            $table->integer('difficulty_id')->unsigned()->nullable();
            $table->foreign('difficulty_id')->references('id')->on('difficulties')->onDelete('set null');
            $table->integer('user_id')->unsigned()->default(1);
            $table->foreign('user_id')->references('id')->on('users');
            $table->text('question');
            $table->string('question_image')->nullable();
            $table->string('answer0')->nullable();
            $table->string('answer0_image')->nullable();
            $table->string('answer1')->nullable();
            $table->string('answer1_image')->nullable();
            $table->string('answer2')->nullable();
            $table->string('answer2_image')->nullable();
            $table->string('answer3')->nullable();
            $table->string('answer3_image')->nullable();
            $table->integer('correct_answer');
            $table->integer('status_id')->unsigned()->default(1);
            $table->foreign('status_id')->references('id')->on('statuses');
            $table->text('source')->nullable();
            $table->integer('type_id')->unsigned()->default(1);
            $table->foreign('type_id')->references('id')->on('types');
            $table->string('calculator')->nullable();
            $table->timestamps();
        });

        Schema::create('question_user', function (Blueprint $table) {
            $table->integer('question_id')->unsigned();
            $table->foreign('question_id')->references('id')->on('questions');
            $table->integer('user_id')->unsigned()->default(1);
            $table->foreign('user_id')->references('id')->on('users');
            $table->integer('quiz_id')->unsigned()->nullable();
            $table->foreign('quiz_id')->references('id')->on('quizzes')->onDelete('cascade');
            $table->integer('test_id')->unsigned()->nullable();
            $table->foreign('test_id')->references('id')->on('tests')->onDelete('cascade');
            $table->string('assessment_type');
            $table->boolean('question_answered')->default(false);
            $table->boolean('correct')->default(false);
            $table->datetime('answered_date')->nullable();
            $table->integer('attempts')->default(0);
            $table->timestamps();
        });

        Schema::create('question_quiz', function (Blueprint $table) {
            $table->integer('quiz_id')->unsigned();
            $table->foreign('quiz_id')->references('id')->on('quizzes');
            $table->integer('question_id')->unsigned()->default(1);
            $table->foreign('question_id')->references('id')->on('questions');
            $table->primary(['quiz_id','question_id']);
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
        Schema::dropIfExists('question_quiz');        
        Schema::drop('question_user');
        Schema::drop('questions');
    }
}
