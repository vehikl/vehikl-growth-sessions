<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;


return new class extends Migration {

    public function up(): void
    {
        Schema::table('growth_session_user', function (Blueprint $table) {
            $table->index(['growth_session_id', 'user_id', 'user_type_id']);
        });
    }

    public function down(): void
    {
        Schema::table('growth_session_user', function (Blueprint $table) {
            $table->dropIndex(['growth_session_id', 'user_id', 'user_type_id']);
        });
    }
};
