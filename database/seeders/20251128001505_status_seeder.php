<?php

declare(strict_types=1);

final class StatusSeeder
{
    public function run(): void
    {
        $user = \App\Models\User::where('email', '=', 'user@example.com')->first();
        if ($user === null) {
            return;
        }

        // Finns redan status för denna user? hoppa.
        $exists = \App\Models\Status::where('user_id', '=', $user->id)->first();
        if ($exists !== null) {
            return;
        }

        // Skapa status via modell (timestamps, mutators etc. hanteras av ORM)
        \App\Models\Status::updateOrCreate(
            ['user_id' => $user->id],
            [
                'password_reset'   => null,
                'reset_expires_at' => null,
                'activation'       => null,
                'status'           => 'activated',
                'active'           => 'offline',
                'active_at'        => null,
            ]
        );
    }

    public function down(): void
    {
        $user = \App\Models\User::where('email', '=', 'user@example.com')->first();
        if (!$user) {
            return;
        }

        // ta bort barn först
        \App\Models\Status::where('user_id', '=', $user->id)->delete();
        $user->forceDelete(); // hård radering, kringgår soft deletes
    }
}
