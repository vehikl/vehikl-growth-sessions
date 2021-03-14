<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class RenameSocialMobToGrowthSession extends Migration
{
    public function up()
    {
        Schema::table('comments', function (Blueprint $table) {
            $table->renameColumn('social_mob_id', 'growth_session_id');
        });

        Schema::table('social_mob_user', function (Blueprint $table) {
            $table->renameColumn('social_mob_id', 'growth_session_id');
        });

        Schema::rename('social_mobs', 'growth_sessions');

        Schema::rename('social_mob_user', 'growth_session_user');
    }

    public function down()
    {
        Schema::rename('growth_session_user', 'social_mob_user');

        Schema::rename('growth_sessions', 'social_mobs');

        Schema::table('social_mob_user', function (Blueprint $table) {
            $table->renameColumn('growth_session_id', 'social_mob_id');
        });

        Schema::table('comments', function (Blueprint $table) {
            $table->renameColumn('growth_session_id', 'social_mob_id');
        });
    }
}
