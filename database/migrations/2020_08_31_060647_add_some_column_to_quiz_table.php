<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class AddSomeColumnToQuizTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('quizzes', function (Blueprint $table) {
            $table->boolean('diagnostic')->default(FALSE);
            $table->string('image')->nullable();
            $table->dateTime('start_available_time')->default(date('Y-m-d', strtotime('-1 day')));
            $table->dateTime('end_available_time')->default(date('Y-m-d', strtotime('+1 year')));
            $table->dateTime('due_time');
            $table->integer('number_of_tries_allowed')->default(2);
            $table->string('which_result')->default('highest');
            $table->integer('status_id')->unsigned()->default(1);
            $table->foreign('status_id')->references('id')->on('statuses');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('quizzes', function (Blueprint $table) {
            //
        });
    }
}
