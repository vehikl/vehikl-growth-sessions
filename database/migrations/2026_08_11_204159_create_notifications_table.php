<?php

use App\Models\GrowthSession;
use App\Models\User;
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
        Schema::create('notifications', function (Blueprint $table) {
            $table->id();
            $table->foreignIdFor(User::class, 'initiator');
            $table->foreignIdFor(User::class);
            $table->foreignIdFor(GrowthSession::class)->nullable();
            $table->boolean('read')->default(false);
            // A list, not a single value: one save can move the date, the time and the location at
            // once, and that is one notification naming three events rather than a composite type.
            $table->json('event_types');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('notifications');
    }
};
