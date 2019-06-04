<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateVideoTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('videos', function (Blueprint $table) {
            $table->increments('id');
            $table->string('video_link');
            $table->string('description');
            $table->timestamps();
            $table->integer('status_id')->unsigned()->nullable()->default(4);
            $table->foreign('status_id')->references('id')->on('statuses')->onDelete('cascade');
            $table->integer('user_id')->unsigned()->nullable()->default(1);
            $table->foreign('user_id')->references('id')->on('users')->onDelete('cascade');
         });

        Schema::table('skilllinks', function($table) {
            $table->integer('video_id')->unsigned()->nullable();
            $table->foreign('video_id')->references('id')->on('videos')->onDelete('cascade');
        });

    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('skilllinks', function($table) {
             $table->dropForeign(['video_id']);
             $table->dropColumn('video_id');
        });
        Schema::drop('videos');
    }
}
