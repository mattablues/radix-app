<?php

declare(strict_types=1);

return [
    'security' => [
        /*
         * Cross-Origin-Resource-Policy
         *
         * Tillåtna värden:
         * - same-origin
         * - same-site
         * - cross-origin
         * - off
         *
         * Tomt/off betyder att frameworkets middleware inte sätter headern.
         */
        'corp' => getenv('SECURITY_CORP') ?: '',
    ],
];
