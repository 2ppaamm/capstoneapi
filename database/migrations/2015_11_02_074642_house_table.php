<?php

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class HouseTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('houses', function (Blueprint $table) {
            $table->increments('id');
            $table->string('house');
            $table->string('description');
            $table->integer('user_id')->unsigned()->default(1);
            $table->foreign('user_id')->references('id')->on('users');
            $table->integer('course_id')->unsigned()->default(1);
            $table->foreign('course_id')->references('id')->on('courses');
            $table->integer('framework_id')->unsigned()->nullable()->default(1);
            $table->foreign('framework_id')->references('id')->on('frameworks');
            $table->string('image')->nullable();
            $table->integer('status_id')->unsigned()->default(4);
            $table->foreign('status_id')->references('id')->on('statuses');
            $table->date('start_date')->nullable();
            $table->date('end_date')->nullable();
            $table->timestamps();
        });

        Schema::create('house_role_user', function (Blueprint $table) {
            $table->integer('house_id')->unsigned()->index()->default(1);
            $table->foreign('house_id')->references('id')->on('houses')->onDelete('cascade');
            $table->integer('role_id')->unsigned()->default(6);
            $table->foreign('role_id')->references('id')->on('roles')->onDelete('cascade');
            $table->integer('user_id')->unsigned()->index();
            $table->foreign('user_id')->references('id')->on('users')->onDelete('cascade');
            $table->integer('progress')->default(0);
            $table->string('transaction_id')->nullable();
            $table->string('payment_email')->nullable()->default('info.all-gifted@gmail.com');
            $table->integer('purchaser_id')->unsigned()->nullable();
            $table->foreign('purchaser_id')->references('id')->on('users')->onDelete('cascade');
            $table->date('start_date')->default(date('Y-m-d'));
            $table->date('expiry_date')->default(date('Y-m-d', strtotime('+1 year')));
            $table->integer('places_alloted')->default(0);
            $table->decimal('amount_paid', 8,2)->nullable();
            $table->string('currency_code')->nullable();
            $table->string('payment_status')->nullable();                        
            $table->timestamps();
            $table->primary(['house_id','role_id', 'user_id']);
        });
        DB::statement('ALTER Table house_role_user add id INTEGER NOT NULL UNIQUE AUTO_INCREMENT;');
        DB::statement('ALTER Table house_role_user add mastercode INTEGER UNIQUE;');

        Schema::create('house_track', function (Blueprint $table) {
            $table->integer('house_id')->unsigned();
     
            $table->foreign('house_id')->references('id')->on('houses')->onDelete('cascade');
            $table->integer('track_id')->unsigned();
            $table->foreign('track_id')->references('id')->on('tracks')->onDelete('cascade');
            $table->float('track_order')->nullable()->default(9999.99);
            $table->date('start_date')->nullable();
            $table->date('end_date')->nullable();
            $table->primary(['house_id','track_id']);
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
        Schema::drop('house_role_user');
        Schema::drop('house_track');
        Schema::drop('houses');        
    }
}
