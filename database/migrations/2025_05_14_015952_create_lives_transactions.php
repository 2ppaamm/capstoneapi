<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::create('lives_transactions', function (Blueprint $table) {
            $table->id();
            $table->integer('user_id')->unsigned();
            $table->foreign('user_id')->references('id')->on('users')->onDelete('cascade');
            $table->enum('type', ['earned', 'purchased', 'used', 'reset'])->index();
            $table->integer('amount'); // e.g., +3 for earned/purchased, -1 for used
            $table->string('reason')->nullable(); // optional description
            $table->timestamps();
        });
    }

    public function down()
    {
        Schema::dropIfExists('lives_transactions');
    }
};

