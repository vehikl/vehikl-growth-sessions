<?php

use App\Models\UserType;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration {
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        // First, find all cases where a user is both owner and attendee of the same growth session
        $duplicates = DB::table('growth_session_user as gsu1')
            ->join('growth_session_user as gsu2', function ($join) {
                $join->on('gsu1.growth_session_id', '=', 'gsu2.growth_session_id')
                    ->on('gsu1.user_id', '=', 'gsu2.user_id')
                    ->where('gsu1.user_type_id', '=', UserType::OWNER_ID)
                    ->where('gsu2.user_type_id', '=', UserType::ATTENDEE_ID);
            })
            ->select('gsu2.growth_session_id', 'gsu2.user_id')
            ->get();

        // Remove the attendee entries for users who are owners of the same growth session
        foreach ($duplicates as $duplicate) {
            DB::table('growth_session_user')
                ->where('growth_session_id', $duplicate->growth_session_id)
                ->where('user_id', $duplicate->user_id)
                ->where('user_type_id', UserType::ATTENDEE_ID)
                ->delete();
        }
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        // Find all owners
        $owners = DB::table('growth_session_user')
            ->where('user_type_id', UserType::OWNER_ID)
            ->get();

        // Re-add them as attendees
        foreach ($owners as $owner) {
            DB::table('growth_session_user')->insert([
                'growth_session_id' => $owner->growth_session_id,
                'user_id' => $owner->user_id,
                'user_type_id' => UserType::ATTENDEE_ID
            ]);
        }
    }
};
