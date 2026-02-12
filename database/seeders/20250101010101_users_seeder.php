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
            'last_name'  => 'Example',
            'email'      => 'admin@example.com',
        ]);
        $user->password = 'secret123'; // triggar setPasswordAttribute och hashar
        $user->role = 'admin';
        $user->save();

        // Skapa API-token för admin
        \App\Models\Token::createToken(
            (int) $user->id,
            'Admin Personal Token',
            365
        );
    }

    public function down(): void
    {
        $user = \App\Models\User::where('email', '=', 'admin@example.com')->first();
        if (!$user) {
            return;
        }

        $token = \App\Models\Token::where('email', '=', 'admin@example.com')->first();
        if (!$token) {
            return;
        }

        \App\Models\Token::where('user_id', '=', $user->id)->delete();
        $token->forceDelete();

        \App\Models\Status::where('user_id', '=', $user->id)->delete(); // barn först
        $user->forceDelete(); // hård radering, kringgår soft deletes
    }
}
