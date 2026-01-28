<?php

declare(strict_types=1);

return [
    // Namespace där dina app-modeller normalt ligger
    'model_namespace' => 'App\\Models\\',

    // Endast undantag: tabellnamn => FQCN (class-string)
    'model_map' => [
        // 'people' => \App\Models\Person::class,
        // 'statuses' => \App\Models\Status::class, // om du har “legacy”-namn t.ex.
    ],
];
