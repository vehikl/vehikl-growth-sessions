<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddIsVehiklOnlyColumnToGrowthSessionsTable extends Migration
{
    public function up()
    {
        Schema::table('growth_sessions', function (Blueprint $table) {
            $table->boolean('is_public')->default(false)->after('end_time');
        });
    }

    public function down()
    {
        Schema::table('growth_sessions', function (Blueprint $table) {
            $table->dropColumn('is_public');
        });
    }
}
