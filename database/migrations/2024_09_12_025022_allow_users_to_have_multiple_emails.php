<?php

use App\Models\Comment;
use App\Models\Email;
use App\Models\GrowthSessionUser;
use App\Models\User;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up()
    {
        Schema::create('emails', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->string('address')->unique();
            $table->timestamps();
        });

        User::query()
            ->select('id', 'email', 'github_nickname')
            ->get()
            ->mapToGroups(fn($user) => [
                $user->github_nickname => ['id' => $user->id, 'email' => $user->email]
            ])
            ->each(function (Collection $entries, string $githubNickname) {
                $mainUserId = $entries->min('id');
                $emails = $entries->map(fn($entry) => [
                    'user_id' => $mainUserId,
                    'address' => $entry['email'],
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);
                $usersToDelete = $entries->where('id', "!=", $mainUserId)->pluck('id');

                Email::query()->insert($emails->toArray());
                GrowthSessionUser::query()
                    ->whereIn('user_id', $usersToDelete)
                    ->update(['user_id' => $mainUserId]);
                Comment::query()
                    ->whereIn('user_id', $usersToDelete)
                    ->update(['user_id' => $mainUserId]);
                User::query()
                    ->whereIn('id', $usersToDelete)
                    ->delete();
            });

        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn('email');
            $table->dropColumn('email_verified_at');
        });
    }

    public function down()
    {
        Schema::table('users', function (Blueprint $table) {
            $table->timestamp('email_verified_at')->after('avatar')->nullable();
            $table->string('email')->after('avatar')->nullable();
        });

        Email::query()
            ->selectRaw('user_id, MIN(address) AS address')
            ->groupBy('user_id')
            ->get()
            ->each(function (Email $email) {
                User::query()->where('id', $email->user_id)->update([
                    'email' => $email->address,
                    'email_verified_at' => now(),
                ]);
            });

        Schema::dropIfExists('emails');
    }
};
