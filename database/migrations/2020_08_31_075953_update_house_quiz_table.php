<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class UpdateHouseQuizTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('house_quiz', function (Blueprint $table) {
            $table->dropForeign(['house_id']);
//            $table->dropColumn('house_id');
//            $table->integer('house_id')->unsigned();
            $table->foreign('house_id')->references('id')->on('houses');
            $table->dropForeign(['quiz_id']);
//            $table->dropColumn('quiz_id');
//            $table->integer('quiz_id')->unsigned()->default(1);
            $table->foreign('quiz_id')->references('id')->on('quizzes');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('house_quiz', function (Blueprint $table) {
            //
        });
    }
}
