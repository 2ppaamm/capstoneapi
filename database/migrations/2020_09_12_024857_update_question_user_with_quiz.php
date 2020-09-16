<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class UpdateQuestionUserWithQuiz extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('question_user', function (Blueprint $table) {
            $table->integer('quiz_id')->unsigned()->nullable();
            $table->foreign('quiz_id')->references('id')->on('quizzes');
            $table->string('assessment_type');
            $table->dropForeign(['question_id']);
            $table->dropForeign(['test_id']);            
            $table->integer('test_id')->unsigned()->nullable()->change();
            $table->dropPrimary();
            $table->foreign('question_id')->references('id')->on('questions');
            $table->foreign('user_id')->references('id')->on('users');
            $table->foreign('test_id')->references('id')->on('tests');
            $table->index(['question_id','user_id']);
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('question_user', function (Blueprint $table) {
            $table->dropForeign(['quiz_id']);
            $table->dropColumn('quiz_id');
            $table->dropColumn('assessment_type');
            $table->dropForeign(['user_id']);
            $table->dropForeign(['question_id']);
            $table->dropIndex(['question_id','user_id']);
            $table->foreign('question_id')->references('id')->on('questions');
            $table->foreign('user_id')->references('id')->on('users');
            $table->primary(['question_id','test_id','user_id']);
            $table->dropForeign(['user_id']);
        });
    }
}
