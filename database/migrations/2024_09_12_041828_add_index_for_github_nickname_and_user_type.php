<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->index(['github_nickname']);
        });

        Schema::table('growth_session_user', function (Blueprint $table) {
            $table->index(['user_id', 'user_type_id']);
            $table->index('user_type_id');
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropIndex(['github_nickname']);
        });

        Schema::table('growth_session_user', function (Blueprint $table) {
            $table->dropIndex(['user_id', 'user_type_id']);
            $table->dropIndex('user_type_id');
        });
    }
};
