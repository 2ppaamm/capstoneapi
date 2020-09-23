<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class ChangeAnswerDefaultToNull extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('questions', function($table) {
            $table->string('answer0')->default(NULL)->change();
            $table->string('answer0_image')->default(NULL)->change();
            $table->string('answer1')->default(NULL)->change();
            $table->string('answer1_image')->default(NULL)->change();
            $table->string('answer2')->default(NULL)->change();
            $table->string('answer2_image')->default(NULL)->change();
            $table->string('answer3')->default(NULL)->change();
            $table->string('answer3_image')->default(NULL)->change();
        });            //
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('questions', function (Blueprint $table) {
            //
        });
    }
}
