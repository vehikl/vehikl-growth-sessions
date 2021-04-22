<?php

use App\UserType;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class AddUserTypeIdColumnToGrowthSessionUserTable extends Migration
{
    public function up()
    {
        Schema::table('growth_session_user', function (Blueprint $table) {
            $table->unsignedBigInteger('user_type_id')->default(UserType::ATTENDEE_ID);

            $table->foreign('user_type_id')
                ->references('id')
                ->on('user_types')
                ->cascadeOnDelete();
        });
    }

    public function down()
    {
        Schema::table('growth_session_user', function (Blueprint $table) {
            $table->dropColumn('user_type_id');
        });
    }
}
