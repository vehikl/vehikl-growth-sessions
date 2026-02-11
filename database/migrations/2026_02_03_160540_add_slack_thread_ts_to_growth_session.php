<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('growth_sessions', function (Blueprint $table) {
            $table->string('slack_thread_ts')->nullable()->index();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('growth_sessions', function (Blueprint $table) {
            $table->dropIndex('growth_sessions_slack_thread_ts_index');
            $table->dropColumn('slack_thread_ts');
        });
    }
};
