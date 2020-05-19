<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateSocialMobsTable extends Migration
{
    public function up()
    {
        Schema::create('social_mobs', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('owner_id');
            $table->string('topic');
            $table->dateTime('start_time');
            $table->timestamps();
        });
    }

    public function down()
    {
        Schema::dropIfExists('social_mobs');
    }
}
