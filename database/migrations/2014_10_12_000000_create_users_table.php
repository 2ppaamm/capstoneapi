<?php

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateUsersTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('users', function (Blueprint $table) {
            $table->increments('id');
            $table->string('name')->nullable();
            $table->string('firstname')->nullable();
            $table->string('lastname')->nullable();
            $table->string('contact')->nullable();
            $table->string('email')->unique()->nullable();
            $table->string('password', 60);
            $table->rememberToken();
            $table->boolean('is_admin');
            $table->decimal('maxile_level', 8,2)->default(0.00);
            $table->integer('game_level')->default(0);
            $table->date('date_of_birth');
            $table->dateTime('last_test_date')->nullable();
            $table->dateTime('next_test_date')->nullable();
            $table->string('image')->nullable();
            $table->boolean('diagnostic')->default(FALSE);
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
        Schema::dropIfExists('users');
    }
}
