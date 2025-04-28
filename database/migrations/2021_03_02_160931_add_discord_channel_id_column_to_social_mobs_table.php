<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddDiscordChannelIdColumnToSocialMobsTable extends Migration
{
    public function up(): void
    {
        Schema::table('social_mobs', function (Blueprint $table) {
            $table->string('discord_channel_id')->nullable(true)->after('location');
        });
    }

    public function down(): void
    {
        if(Schema::hasColumn('social_mobs', 'discord_channel_id')) {
            Schema::table('social_mobs', function (Blueprint $table) {
                $table->dropColumn('discord_channel_id');
            });
        }
    }
}
