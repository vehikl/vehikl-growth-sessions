<?php

use App\UserType;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class RemoveOwnerIdColumnFromGrowthSessionsTable extends Migration{
    public function up()
    {
        \App\GrowthSession::all()->each(function($growthsession) {
            DB::table('growth_session_user')->insert([
                'growth_session_id' => $growthsession->id,
                'user_id' => $growthsession->owner_id,
                'user_type_id' => UserType::OWNER_ID
            ]);
        });

        Schema::table('growth_sessions', function (Blueprint $table) {
            if (config('database.default') !== "sqlite") {
                $table->dropForeign('social_mobs_owner_id_foreign');
            }
            $table->dropColumn('owner_id');
        });
    }

    public function down()
    {
        Schema::table('growth_sessions', function (Blueprint $table) {
            $table->foreignId('owner_id')->after('id')->nullable()->constrained('users')->cascadeOnDelete();
        });

        \App\GrowthSession::all()->each(function($growthsession) {
            $growthsession->update(['owner_id' => $growthsession->owner->id]);
        });

        Schema::table('growth_sessions', function (Blueprint $table) {
            $table->foreignId('owner_id')->nullable(false)->change();
        });

        DB::table('growth_session_user')->where('user_type_id', UserType::OWNER_ID)->delete();
    }
}
