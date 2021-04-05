<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use App\User;

class AddUserTypeColumnToGrowthSessionUserTable extends Migration
{

    public function up()
    {
        Schema::table('growth_session_user', function (Blueprint $table) {
            $table->enum('user_type', [User::OWNER, User::ATTENDEE])->default(User::ATTENDEE);
        });
    }

    public function down()
    {
        Schema::table('growth_session_user', function (Blueprint $table) {
            $table->dropColumn('user_type');
        });
    }
}
