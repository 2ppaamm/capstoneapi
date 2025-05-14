<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up()
    {
        Schema::create('user_lives', function (Blueprint $table) {
            $table->id();
            $table->integer('user_id')->unsigned();
            $table->foreign('user_id')->references('id')->on('users')->onDelete('cascade');
            $table->unsignedTinyInteger('lives_remaining')->default(2); // Default for free users
            $table->date('last_reset')->nullable();                     // Reset once per day
            $table->boolean('is_unlimited')->default(false);            // Premium users
            $table->enum('user_tier', ['free', 'basic', 'premium'])->default('free');

            $table->timestamps();
        });
    }

    public function down()
    {
        Schema::dropIfExists('user_lives');
    }
};
