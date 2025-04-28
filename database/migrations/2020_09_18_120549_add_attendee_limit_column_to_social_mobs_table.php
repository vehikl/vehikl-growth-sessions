<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddAttendeeLimitColumnToSocialMobsTable extends Migration
{
    public function up()
    {
        Schema::table('social_mobs', function (Blueprint $table) {
            $table->unsignedBigInteger('attendee_limit')->default(PHP_INT_MAX);
        });
    }

    public function down()
    {
        Schema::table('social_mobs', function (Blueprint $table) {
            $table->dropColumn('attendee_limit');
        });
    }
}
