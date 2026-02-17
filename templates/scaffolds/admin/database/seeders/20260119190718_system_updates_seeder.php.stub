<?php

declare(strict_types=1);

final class SystemUpdatesSeeder
{
    public function run(): void
    {
        $version = 'v1.0.0';

        // Kontrollera om versionen redan finns för att undvika dubbletter
        $systemUpdates = \App\Models\SystemUpdate::where('version', '=', $version)
            ->first();

        if ($systemUpdates) {
            return;
        }

        $systemUpdates = new \App\Models\SystemUpdate();
        $systemUpdates->fill([
            'version'     => $version,
            'title'       => 'Radix Lanserad',
            'description' => 'Stabil version med fullt stöd för Tailwind v4 och optimerad esbuild-pipeline',
            'released_at' => date('Y-m-d H:i:s'),
            'is_major'    => true,
        ]);

        $systemUpdates->save();
    }

    public function down(): void
    {
        // Ta bort version 1.0.0 om vi rullar tillbaka seedern
        $systemUpdate = \App\Models\SystemUpdate::where('version', '=', '1.0.0')
            ->first();

        if (!$systemUpdate) {
            return;
        }

        // ta bort barn först
        \App\Models\SystemUpdate::where('version', '=', '1.0.0')
            ->delete();
        $systemUpdate->forceDelete(); // hård radering, kringgår soft deletes
    }
}
