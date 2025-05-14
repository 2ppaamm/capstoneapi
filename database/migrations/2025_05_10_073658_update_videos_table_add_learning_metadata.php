<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('videos', function (Blueprint $table) {
            $table->unsignedBigInteger('field_id')->nullable()->after('user_id');
            $table->unsignedBigInteger('track_id')->nullable()->after('field_id');
            $table->unsignedBigInteger('skill_id')->nullable()->after('track_id');

            $table->string('video_title')->nullable()->after('video_link');
            $table->integer('order')->nullable()->after('video_title');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('videos', function (Blueprint $table) {
            $table->dropColumn(['field_id', 'track_id', 'skill_id', 'video_title', 'order']);
        });
    }
};
