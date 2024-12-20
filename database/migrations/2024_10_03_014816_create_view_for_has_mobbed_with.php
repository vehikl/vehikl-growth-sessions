<?php

use Illuminate\Database\Migrations\Migration;

return new class extends Migration {
    public function up(): void
    {
        DB::statement("DROP VIEW IF EXISTS user_has_mobbed_with");
        DB::statement("
            CREATE VIEW user_has_mobbed_with AS
            SELECT DISTINCT u1.id AS main_user_id, 
                            u2.id AS other_user_id, 
                            CASE 
                                WHEN participated.user_1 IS NOT NULL THEN 1
                                ELSE 0
                            END AS has_mobbed
            FROM users AS u1
                JOIN users AS u2 ON u1.id <> u2.id 
                LEFT JOIN (
                    SELECT DISTINCT gsu1.user_id AS user_1, gsu2.user_id AS user_2
                    FROM growth_session_user AS gsu1
                    JOIN growth_session_user AS gsu2
                        ON gsu1.growth_session_id = gsu2.growth_session_id
                        AND gsu1.user_id <> gsu2.user_id
                    JOIN (
                        SELECT growth_session_id
                        FROM growth_session_user
                        WHERE user_type_id = 2
                        GROUP BY growth_session_id
                        HAVING COUNT(user_id) <= 10
                    ) AS valid_sessions
                        ON gsu1.growth_session_id = valid_sessions.growth_session_id
                    WHERE gsu1.user_type_id = 2
                    AND gsu2.user_type_id = 2
                ) AS participated ON u1.id = participated.user_1 AND u2.id = participated.user_2
            WHERE u1.is_visible_in_statistics = 1
            AND u2.is_visible_in_statistics = 1;
        ");
    }

    public function down(): void
    {
        DB::statement("DROP VIEW IF EXISTS user_has_mobbed_with");
    }
};
