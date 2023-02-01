<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddAllowWatchersColumnToGrowthSessionsTable extends Migration
{
    public function up()
    {
        Schema::table('growth_sessions', function (Blueprint $table) {
            $table->boolean('allow_watchers')->default(true)->after('is_public');
        });
    }
    public function down()
    {
        Schema::table('growth_sessions', function (Blueprint $table) {
            $table->dropColumn('allow_watchers');
        });
    }
}
