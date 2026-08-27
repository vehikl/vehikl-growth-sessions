<?php

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
            ['id' => 1, 'type' => 'owner'],
            ['id' => 2, 'type' => 'attendee'],
            ['id' => 3, 'type' => 'watcher'],
        ]);
    }

    public function down()
    {
        Schema::dropIfExists('user_types');
    }
}
