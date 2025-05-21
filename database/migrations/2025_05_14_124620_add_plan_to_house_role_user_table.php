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
        // 1. Create the 'plans' table
        Schema::create('plans', function (Blueprint $table) {
            $table->id();
            $table->string('name')->unique(); // e.g., free, basic, premium
            $table->integer('default_lives')->nullable(); // null means unlimited
            $table->timestamps();
        });

        // 2. Add 'plan_id' to the 'house_role_user' table
        Schema::table('house_role_user', function (Blueprint $table) {
            $table->unsignedBigInteger('plan_id')->nullable()->after('user_id');

            $table->foreign('plan_id')
                  ->references('id')
                  ->on('plans')
                  ->onDelete('set null');       });
    }

    public function down()
    {
        // 1. Drop the foreign key and column from 'house_role_user'
        Schema::table('house_role_user', function (Blueprint $table) {
            $table->dropForeign(['plan_id']);
            $table->dropColumn('plan_id');
        });

        // 2. Drop the 'plans' table
        Schema::dropIfExists('plans');
    }
};
