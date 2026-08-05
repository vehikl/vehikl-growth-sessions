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
            // A binary collation, so that tokens are matched case-sensitively — the default
            // case-insensitive collation would both shrink the token's entropy and reject
            // two tokens differing only in case as duplicates.
            $table->string('share_token')->nullable()->charset('ascii')->collation('ascii_bin')->unique();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('growth_sessions', function (Blueprint $table) {
            $table->dropUnique(['share_token']);
            $table->dropColumn('share_token');
        });
    }
};
