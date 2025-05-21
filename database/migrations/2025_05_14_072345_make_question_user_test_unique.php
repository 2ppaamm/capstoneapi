<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('question_user', function (Blueprint $table) {
            // Add a composite unique key to prevent duplicate entries
            $table->unique(['question_id', 'test_id', 'user_id'], 'question_user_unique');
        });
    }

    public function down(): void
    {
        Schema::table('question_user', function (Blueprint $table) {
            $table->dropUnique('question_user_unique');
        });
    }
};
