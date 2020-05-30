<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddEndTimeColumnToSocialMobs extends Migration
{
    public function up()
    {
        Schema::table('social_mobs', function (Blueprint $table) {
            $table->addColumn('dateTime', 'end_time')->after('start_time')->nullable();
        });
    }

    public function down()
    {
        Schema::table('social_mobs', function (Blueprint $table) {
            $table->removeColumn('end_time');
        });
    }
}
