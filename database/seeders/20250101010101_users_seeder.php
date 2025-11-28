<?php

declare(strict_types=1);

final class UsersSeeder
{
    public function run(): void
    {
        $user = \App\Models\User::where('email', '=', 'admin@example.com')->first();
        if ($user) {
            return;
        }

        $user = new \App\Models\User();
        $user->fill([
            'first_name' => 'Admin',
            'last_name'  => 'User',
            'email'      => 'admin@example.com',
            'role'       => 'admin',
        ]);
        $user->password = 'secret'; // triggar setPasswordAttribute och hashar
        $user->save();
    }

    public function down(): void
    {
        $user = \App\Models\User::where('email', '=', 'admin@example.com')->first();
        if (!$user) return;

        \App\Models\Status::where('user_id', '=', $user->id)->delete(); // barn först
        $user->forceDelete(); // hård radering, kringgår soft deletes
    }
}