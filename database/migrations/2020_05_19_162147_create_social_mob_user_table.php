<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateSocialMobUserTable extends Migration
{
    public function up()
    {
        Schema::create('social_mob_user', function (Blueprint $table) {
            $table->unsignedBigInteger('user_id');
            $table->unsignedBigInteger('social_mob_id');

            $table->foreign('user_id')->references('id')->on('users');
            $table->foreign('social_mob_id')->references('id')->on('social_mobs');
        });
    }

    public function down()
    {
        Schema::dropIfExists('social_mob_user');
    }
}
