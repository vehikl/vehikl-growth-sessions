<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up()
    {
        $allEntries = DB::table('growth_session_user')->get();

        DB::table('growth_session_user')->truncate();

        Schema::table('growth_session_user', function (Blueprint $table) {
            $table->primary(['user_id', 'growth_session_id', 'user_type_id']);
        });

        $entriesPerPage = 500;
        $numberOfPages = ceil($allEntries->count() / $entriesPerPage);
        for ($i = 0; $i <= $numberOfPages; $i++) {
            DB::table('growth_session_user')
                ->insertOrIgnore(json_decode($allEntries->forPage($i, $entriesPerPage)->toJson(), TRUE));
        }
    }

    public function down()
    {

    }
};
