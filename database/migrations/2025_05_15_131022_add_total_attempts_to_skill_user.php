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
            $table->integer('total_correct_attempts')->default(0)->after('correct_streak');
            $table->integer('total_incorrect_attempts')->default(0)->after('total_correct_attempts');
        });
    }

    public function down()
    {
        Schema::table('skill_user', function (Blueprint $table) {
            $table->dropColumn(['total_correct_attempts', 'total_incorrect_attempts']);
        });
    }
};
