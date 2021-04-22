<?php

use App\UserType;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class CreateUserTypesTable extends Migration
{
    public function up()
    {
        Schema::create('user_types', function (Blueprint $table) {
            $table->id();
            $table->string('type');
        });

        DB::table('user_types')->insert([
            ['id' => UserType::OWNER_ID, 'type' => UserType::OWNER],
            ['id' => UserType::ATTENDEE_ID, 'type' => UserType::ATTENDEE],
            ['id' => UserType::WATCHER_ID, 'type' => UserType::WATCHER],
        ]);
    }

    public function down()
    {
        Schema::dropIfExists('user_types');
    }
}
