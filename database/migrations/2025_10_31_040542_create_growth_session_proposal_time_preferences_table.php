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
        Schema::create('growth_session_proposal_time_preferences', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('growth_session_proposal_id');
            $table->enum('weekday', ['Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday']);
            $table->time('start_time');
            $table->time('end_time');
            $table->timestamps();

            $table->foreign('growth_session_proposal_id', 'gsp_time_pref_proposal_fk')
                ->references('id')
                ->on('growth_session_proposals')
                ->cascadeOnDelete();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('growth_session_proposal_time_preferences');
    }
};
