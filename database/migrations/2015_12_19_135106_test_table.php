<?php

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class TestTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('tests', function (Blueprint $table) {
            $table->increments('id');
            $table->string('test');
            $table->string('description');
            $table->boolean('diagnostic')->default(FALSE);
            $table->string('image')->nullable();
            $table->dateTime('start_available_time')->default(date('Y-m-d', strtotime('-1 day')));
            $table->dateTime('end_available_time')->default(date('Y-m-d', strtotime('+1 year')));
            $table->dateTime('due_time');
            $table->integer('number_of_tries_allowed')->default(2);
            $table->string('which_result')->default('highest');
            $table->integer('status_id')->unsigned()->default(1);
            $table->foreign('status_id')->references('id')->on('statuses');
            $table->integer('user_id')->unsigned()->default(2);
            $table->foreign('user_id')->references('id')->on('users')->ondelete('cascade');
            $table->integer('level_id')->unsigned()->nullable()->default(NULL);
            $table->foreign('level_id')->references('id')->on('levels');
            $table->decimal('test_maxile')->default(0.00);
            $table->integer('noOfSkillsPassed')->default(0);            
            $table->timestamps();
        });

        Schema::create('test_user', function (Blueprint $table) {
            $table->integer('test_id')->unsigned();
            $table->foreign('test_id')->references('id')->on('tests');
            $table->integer('user_id')->unsigned()->default(1);
            $table->foreign('user_id')->references('id')->on('users');
            $table->boolean('test_completed')->default(false);
            $table->date('completed_date')->nullable();
            $table->decimal('result', 8,2)->nullable();
            $table->integer('attempts')->default(0);
            $table->primary(['test_id','user_id']);
            $table->timestamps();
        });

        Schema::create('house_test', function (Blueprint $table) {
            $table->integer('house_id')->unsigned();
            $table->foreign('house_id')->references('id')->on('houses');
            $table->integer('test_id')->unsigned()->default(1);
            $table->foreign('test_id')->references('id')->on('tests');
            $table->date('start_date');
            $table->date('end_date');
            $table->decimal('result', 3,2);
            $table->integer('attempts');
            $table->integer('which_attempt');
            $table->primary(['house_id','test_id']);
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
        Schema::drop('house_test');
        Schema::drop('test_user');
        Schema::drop('tests');
    }
}
