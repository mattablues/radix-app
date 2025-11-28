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
            'first_name' => 'Mats',
            'last_name'  => 'Åkebrand',
            'email'      => 'mats@akebrands.se',
        ]);
        $user->password = 'korvar65'; // triggar setPasswordAttribute och hashar
        $user->role = 'admin';
        $user->save();
    }

    public function down(): void
    {
        $user = \App\Models\User::where('email', '=', 'mats@akebrands.se')->first();
        if (!$user) return;

        \App\Models\Status::where('user_id', '=', $user->id)->delete(); // barn först
        $user->forceDelete(); // hård radering, kringgår soft deletes
    }
}