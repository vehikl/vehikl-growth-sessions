<?php

use App\UserType;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration {
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up(): void
    {
        DB::statement("DROP VIEW IF EXISTS user_has_mobbed_with");
        DB::statement("
            CREATE VIEW user_has_mobbed_with AS
            SELECT u1.id AS main_user_id,
                u2.id AS other_user_id,
                u1.name AS main_user_name,
                u2.name AS other_user_name,
                participated.main_user_type_id AS main_user_type_id,
                participated.other_user_type_id AS other_user_type_id,
                participated.growth_session_id AS growth_session_id,
                participated.total_attendees AS total_number_of_attendees
            FROM users AS u1
                JOIN users AS u2 ON u1.id <> u2.id
                LEFT JOIN (
                    SELECT gsu1.user_id AS main_user_id,
                           gsu1.user_type_id AS main_user_type_id,
                           gsu2.user_id AS other_user_id,
                           gsu2.user_type_id AS other_user_type_id,
                           gsu1.growth_session_id,
                           attendee_count.total_attendees
                    FROM growth_session_user AS gsu1
                    JOIN growth_session_user AS gsu2
                        ON gsu1.growth_session_id = gsu2.growth_session_id
                        AND gsu1.user_id <> gsu2.user_id
                    JOIN (
                        SELECT growth_session_id, COUNT(user_id) AS total_attendees
                        FROM growth_session_user
                        WHERE user_type_id IN (" . UserType::ATTENDEE_ID . ", " . UserType::OWNER_ID . ")
                        GROUP BY growth_session_id
                    ) AS attendee_count ON gsu1.growth_session_id = attendee_count.growth_session_id
                ) AS participated ON u1.id = participated.main_user_id AND u2.id = participated.other_user_id
            WHERE u1.is_visible_in_statistics = 1
            AND u2.is_visible_in_statistics = 1;
        ");
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down(): void
    {
        DB::statement("DROP VIEW IF EXISTS user_has_mobbed_with");
        DB::statement("
            CREATE VIEW user_has_mobbed_with AS
            SELECT u1.id AS main_user_id,
                u2.id AS other_user_id,
                u1.name AS main_user_name,
                u2.name AS other_user_name,
                participated.main_user_type_id AS main_user_type_id,
                participated.other_user_type_id AS other_user_type_id,
                participated.growth_session_id AS growth_session_id,
                participated.total_attendees AS total_number_of_attendees
            FROM users AS u1
                JOIN users AS u2 ON u1.id <> u2.id
                LEFT JOIN (
                    SELECT gsu1.user_id AS main_user_id,
                           gsu1.user_type_id AS main_user_type_id,
                           gsu2.user_id AS other_user_id,
                           gsu2.user_type_id AS other_user_type_id,
                           gsu1.growth_session_id,
                           attendee_count.total_attendees
                    FROM growth_session_user AS gsu1
                    JOIN growth_session_user AS gsu2
                        ON gsu1.growth_session_id = gsu2.growth_session_id
                        AND gsu1.user_id <> gsu2.user_id
                    JOIN (
                        SELECT growth_session_id, COUNT(user_id) AS total_attendees
                        FROM growth_session_user
                        WHERE user_type_id = " . UserType::ATTENDEE_ID . "
                        GROUP BY growth_session_id
                    ) AS attendee_count ON gsu1.growth_session_id = attendee_count.growth_session_id
                ) AS participated ON u1.id = participated.main_user_id AND u2.id = participated.other_user_id
            WHERE u1.is_visible_in_statistics = 1
            AND u2.is_visible_in_statistics = 1;
        ");
    }
};
