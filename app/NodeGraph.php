<?php

namespace App;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;

class NodeGraph extends Model
{
    use HasFactory;

    static function getData()
    {
        $nodes = DB::table('users')
            ->select('id', 'name')
            ->where('is_vehikl_member', 1)
            ->get();

        $edges = DB::select(
            DB::raw(
        "SELECT user_id1, user_id2, COUNT(data.user_id1) times_mobbed_together
                FROM (
                    SELECT gsu1.user_id as user_id1, gsu2.user_id as user_id2
                    FROM growth_session_user gsu1
                    JOIN growth_session_user gsu2 ON gsu1.growth_session_id = gsu2.growth_session_id AND gsu1.user_id <> gsu2.user_id
                    ) data
                GROUP BY data.user_id1, data.user_id2;"
            )
        );

        return ['nodes' => $nodes, 'edges' => $edges];
    }
}
