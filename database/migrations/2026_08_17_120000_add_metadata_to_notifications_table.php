<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * A point-in-time copy of the growth session details a notification needs to stay
     * readable once the session row itself is gone. Nullable: only deletion notifications
     * are read from here, and older rows predate the column.
     */
    public function up(): void
    {
        Schema::table('notifications', function (Blueprint $table) {
            $table->json('metadata')->nullable()->after('event_types');
        });
    }

    public function down(): void
    {
        Schema::table('notifications', function (Blueprint $table) {
            $table->dropColumn('metadata');
        });
    }
};
