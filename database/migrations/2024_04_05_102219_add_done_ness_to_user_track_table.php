<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class AddDoneNessToUserTrackTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('track_user', function (Blueprint $table) {
            $table->decimal('doneNess', 5, 2)->default(0); // Adds the doneNess column with two decimal places
        }); }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('track_user', function (Blueprint $table) {
            $table->dropColumn('doneNess');
        });
    }
}
