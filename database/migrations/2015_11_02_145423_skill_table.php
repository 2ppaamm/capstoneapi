<?php

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class SkillTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('skills', function (Blueprint $table) {
            $table->increments('id');
            $table->string('skill');
            $table->string('description');
            $table->integer('user_id')->unsigned()->default(1);
            $table->foreign('user_id')->references('id')->on('users');
            $table->string('image')->nullable();
            $table->string('lesson_link')->nullable();
            $table->integer('status_id')->unsigned()->default(1);
            $table->foreign('status_id')->references('id')->on('statuses');
            $table->integer('track_id')->unsigned()->nullable();
            $table->timestamps();
        });

        Schema::create('skill_track', function (Blueprint $table) {
            $table->integer('track_id')->unsigned();
            $table->foreign('track_id')->references('id')->on('tracks');
            $table->integer('skill_order');
            $table->integer('skill_id')->unsigned()->default(1);
            $table->foreign('skill_id')->references('id')->on('skills');
            $table->date('start_date')->nullable();
            $table->date('end_date')->nullable();
            $table->primary(['track_id','skill_id']);
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
        Schema::drop('skill_track');
        Schema::drop('skills');
    }
}
