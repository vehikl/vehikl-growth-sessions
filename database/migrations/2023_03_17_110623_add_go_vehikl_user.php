<?php

use App\User;
use Illuminate\Database\Migrations\Migration;

return new class extends Migration {
    public function up()
    {
        if (empty(User::where('email', 'go@vehikl.com')->first())) {
            User::create([
                'name' => 'Vehikl',
                'avatar' => 'https://avatars.githubusercontent.com/u/6425636?s=200&v=4',
                'github_nickname' => 'vehikl',
                'email' => 'go@vehikl.com',
                'is_vehikl_member' => 1,
                'password' => \Illuminate\Support\Str::random(),
            ]);
        }
    }

    public function down()
    {
        User::where('email', 'go@vehikl.com')->first()->forceDelete();
    }
};
