<?php

use App\Models\User;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->boolean('is_visible_in_statistics')->default(true)->index();
        });

        User::query()->vehikaliens()->update(['is_visible_in_statistics' => true]);
        User::query()->vehikaliens(false)->update(['is_visible_in_statistics' => false]);
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropIndex(['is_visible_in_statistics']);

            $table->dropColumn('is_visible_in_statistics');
        });
    }
};
