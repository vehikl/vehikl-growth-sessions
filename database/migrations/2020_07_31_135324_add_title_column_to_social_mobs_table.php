<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddTitleColumnToSocialMobsTable extends Migration
{
    public function up()
    {
        Schema::table('social_mobs', function (Blueprint $table) {
            $table->string('title')->nullable();
        });
    }

    public function down()
    {
        Schema::table('social_mobs', function (Blueprint $table) {
            $table->dropColumn('title');
        });
    }
}
