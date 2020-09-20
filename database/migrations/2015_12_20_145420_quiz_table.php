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
            $table->boolean('diagnostic')->default(FALSE);
            $table->string('image')->nullable();
            $table->dateTime('start_available_time')->default(date('Y-m-d', strtotime('-1 day')));
            $table->dateTime('end_available_time')->default(date('Y-m-d', strtotime('+1 year')));
            $table->dateTime('due_time');
            $table->integer('status_id')->unsigned()->default(1);
            $table->foreign('status_id')->references('id')->on('statuses');
            $table->timestamps();
        });

        Schema::create('quiz_user', function (Blueprint $table) {
            $table->integer('quiz_id')->unsigned();
            $table->foreign('quiz_id')->references('id')->on('quizzes');
            $table->integer('user_id')->unsigned()->default(1);
            $table->foreign('user_id')->references('id')->on('users');
            $table->boolean('quiz_completed')->default(false);
            $table->date('completed_date')->nullable();
            $table->decimal('result', 8,2)->nullable();
            $table->integer('attempts')->default(0);
            $table->primary(['quiz_id','user_id']);
            $table->timestamps();
        });

        Schema::create('house_quiz', function (Blueprint $table) {
            $table->integer('house_id')->unsigned();
            $table->foreign('house_id')->references('id')->on('quizzes');
            $table->integer('quiz_id')->unsigned()->default(1);
            $table->foreign('quiz_id')->references('id')->on('users');
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
        Schema::dropIfExists('house_quiz');
        Schema::dropIfExists('quiz_user');        
        Schema::dropIfExists('quizzes');
    }
}
