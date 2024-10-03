<?php

use Illuminate\Database\Migrations\Migration;

return new class extends Migration {
    public function up(): void
    {
        DB::statement("DROP VIEW IF EXISTS user_has_mobbed_with");
        DB::statement("
            CREATE VIEW user_has_mobbed_with AS
            SELECT u1.id AS main_user_id, u2.id AS other_user_id, MAX(CASE WHEN participated.other_user_id IS NOT NULL THEN 1 ELSE 0 END) AS has_mobbed
            FROM users AS u1
            JOIN users AS u2 ON u1.id <> u2.id
            LEFT JOIN (
                SELECT gsu1.user_id AS main_user_id, gsu2.user_id AS other_user_id
                 FROM growth_session_user AS gsu1
                 JOIN growth_session_user AS gsu2 ON gsu1.growth_session_id = gsu2.growth_session_id AND gsu1.user_id <> gsu2.user_id
            ) AS participated ON u1.id = participated.main_user_id AND u2.id = participated.other_user_id
            WHERE u1.is_visible_in_statistics = 1
            AND u2.is_visible_in_statistics = 1
            GROUP BY u1.id, u2.id;
        ");
    }

    public function down(): void
    {
        DB::statement("DROP VIEW IF EXISTS user_has_mobbed_with");
    }
};
