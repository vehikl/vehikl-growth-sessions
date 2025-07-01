<?php

use App\Models\User;
use Illuminate\Database\Migrations\Migration;

return new class extends Migration {
    public function up(): void
    {
        User::query()
            ->whereIn('github_nickname', [
                config('auth.slack_app_name'),
                ...config('auth.vehikl_names'),
            ])
            ->update(['is_visible_in_statistics' => false]);
    }
};
