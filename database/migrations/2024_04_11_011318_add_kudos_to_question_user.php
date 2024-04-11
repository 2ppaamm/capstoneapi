<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class AddKudosToQuestionUser extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('question_user', function (Blueprint $table) {
            // Assuming 'kudos' is an integer
            $table->integer('kudos')->default(0);
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
            $table->dropColumn('kudos');
        });
    }
}
