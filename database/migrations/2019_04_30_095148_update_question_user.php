<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class UpdateQuestionUser extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('question_user', function($table) {
          $table->dropForeign(['user_id']);
//          $table->dropPrimary(['user_id']);
        });
     }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
         Schema::table('question_user', function($table) {
            $table->foreign('user_id')->references('id')->on('users');
         });
   }
}
