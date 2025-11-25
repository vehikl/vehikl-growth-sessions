<?php

use App\User;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropIndex(['is_visible_in_statistics']);
            $table->renameColumn('is_visible_in_statistics', 'temp');
        });

        Schema::table('users', function (Blueprint $table) {
            $table->boolean('is_visible_in_statistics')->after('is_vehikl_member')->default(true)->index();
        });

        User::query()->where('temp', true)->update(['is_visible_in_statistics' => true]);
        User::query()->where('temp', false)->update(['is_visible_in_statistics' => false]);

        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn('temp');
        });
    }
};
