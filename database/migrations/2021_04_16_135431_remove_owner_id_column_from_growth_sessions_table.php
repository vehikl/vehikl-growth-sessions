<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class RemoveOwnerIdColumnFromGrowthSessionsTable extends Migration
{
    public function up()
    {
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
            //
        });
    }
}
