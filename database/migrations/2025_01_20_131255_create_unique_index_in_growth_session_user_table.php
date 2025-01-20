<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('growth_session_user', function (Blueprint $table) {
            $table->unique(['growth_session_id', 'user_id']);
        });
    }

    public function down(): void
    {
        Schema::table('growth_session_user', function (Blueprint $table) {
            $table->dropUnique(['growth_session_id', 'user_id']);
        });
    }
};
