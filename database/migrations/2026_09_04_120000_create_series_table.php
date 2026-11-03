<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('series', function (Blueprint $table) {
            $table->id();
            $table->foreignId('owner_id')->constrained('users')->cascadeOnDelete();
            $table->string('name', 45);
            $table->timestamps();

            $table->unique(['owner_id', 'name']);
        });

        Schema::table('growth_sessions', function (Blueprint $table) {
            $table->foreignId('series_id')->nullable()->after('id')->constrained('series')->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('growth_sessions', function (Blueprint $table) {
            $table->dropConstrainedForeignId('series_id');
        });

        Schema::dropIfExists('series');
    }
};
