<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class RenameNoOfSkillsPassedToTestScoreInTestsTable extends Migration
{
    public function up()
    {
        Schema::table('tests', function (Blueprint $table) {
            $table->decimal('test_score', 5, 2)->default(0)->after('kudos_earned');
            $table->dropColumn('noOfSkillsPassed');
            $table->integer('questions_answered')->default(0)->after('test_score');
            $table->boolean('completed')->default(false)->after('test_score');


        });
    }

    public function down()
    {
        Schema::table('tests', function (Blueprint $table) {
            $table->integer('noOfSkillsPassed')->default(0)->after('kudos_earned');
            $table->dropColumn('completed');
            $table->dropColumn('test_score');
            $table->dropColumn('questions_answered');
        });
    }
}

