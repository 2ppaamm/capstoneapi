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
        Schema::table('skill_user', function (Blueprint $table) {
            $table->renameColumn('noOfPasses', 'correct_streak');
            $table->renameColumn('noOfFails', 'fail_streak');
        });
    }

    public function down()
    {
        Schema::table('skill_user', function (Blueprint $table) {
            $table->renameColumn('correct_streak', 'noOfPasses');
            $table->renameColumn('fail_streak', 'noOfFails');
        });
    }
};
