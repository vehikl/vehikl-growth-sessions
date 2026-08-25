<?php

use App\Models\UserType;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        DB::table('user_types')->insertOrIgnore([
            ['id' => UserType::WAITLISTED_ID, 'type' => UserType::WAITLISTED],
        ]);

        Schema::table('growth_session_user', function (Blueprint $table) {
            // The pivot carries no timestamps of its own, and the queue is served in the order it
            // was joined - so the moment somebody asked has to be recorded here. Sub-second
            // precision because two people can go after the same last seat within the same second.
            $table->timestamp('waitlisted_at', 6)->nullable();
        });
    }

    public function down(): void
    {
        Schema::table('growth_session_user', function (Blueprint $table) {
            $table->dropColumn('waitlisted_at');
        });

        // Cascades through the foreign key, taking every waitlist enrolment with it.
        DB::table('user_types')->where('id', UserType::WAITLISTED_ID)->delete();
    }
};
