<?php

declare(strict_types=1);

try {
    $container = require ROOT_PATH . '/config/services.php';
} catch (\Throwable $e) {
    $env = strtolower((string) getenv('APP_ENV'));
    $isProd = in_array($env, ['prod', 'production'], true);

    $msg = '[bootstrap] config/services.php failed: ' . $e->getMessage();
    error_log($msg);

    if (PHP_SAPI === 'cli') {
        fwrite(STDERR, $msg . PHP_EOL);
        exit(1);
    }

    http_response_code(500);
    echo $isProd
        ? 'Application misconfigured. Check server logs.'
        : ('Bootstrap error: ' . $e->getMessage());
    exit;
}

ini_set('session.gc_maxlifetime', '1200'); // 20 minuter
ini_set('session.gc_probability', '1');
ini_set('session.gc_divisor', '100');

date_default_timezone_set(getApplicationTimezone());

set_error_handler('\Radix\Error\RadixErrorHandler::handleError');
set_exception_handler('\Radix\Error\RadixErrorHandler::handleException');

if (is_running_from_console()) {
    $env = strtolower((string) getenv('APP_ENV'));
    $isProd = in_array($env, ['prod', 'production'], true);

    if ($isProd) {
        $argv = $GLOBALS['argv'] ?? [];
        $cmd = is_array($argv) && isset($argv[1]) && is_string($argv[1]) ? trim($argv[1]) : '';

        $isDeploy = getenv('RADIX_DEPLOY') === '1';

        // Alltid OK i prod (låg risk)
        $allowAlways = [
            'cache:clear',
        ];

        // OK i prod endast vid deploy (medvetet "armad" körning)
        $allowWhenDeploy = [
            'migrations:migrate',
        ];

        // Extra tydlighet: kommandon vi aktivt vill avråda från i prod
        $blockedInProd = [
            'app:setup' => 'Kör dev/demo-seeders och är inte avsett för production.',
            'migrations:rollback' => 'Rollback i production kan ge dataförlust/inkonsistens.',
            'seeds:run' => 'Seeders är dev/demo i detta projekt.',
            'seeds:rollback' => 'Seeders är dev/demo i detta projekt.',
            'scaffold:install' => 'Scaffold ska köras i dev/CI, inte i production.',
        ];

        // Blocka hela prefix-grupper också (tydligt för MIT-användare)
        $blockedPrefixes = [
            'make:' => 'Kodgeneratorer ska inte köras i production.',
            'seeds:' => 'Seeders är dev/demo i detta projekt.',
            'scaffold:' => 'Scaffold ska köras i dev/CI, inte i production.',
        ];

        $denyReason = null;

        if ($cmd === '') {
            $denyReason = 'Inget kommando angavs.';
        }

        if ($denyReason === null && isset($blockedInProd[$cmd])) {
            $denyReason = $blockedInProd[$cmd];
        }

        if ($denyReason === null) {
            foreach ($blockedPrefixes as $prefix => $reason) {
                if (str_starts_with($cmd, $prefix)) {
                    $denyReason = $reason;
                    break;
                }
            }
        }

        $allowed = $denyReason === null && (
            in_array($cmd, $allowAlways, true)
            || ($isDeploy && in_array($cmd, $allowWhenDeploy, true))
        );

        if (!$allowed) {
            http_response_code(403);

            fwrite(STDERR, "Forbidden: CLI command not allowed in production." . PHP_EOL);
            fwrite(STDERR, "Command: " . ($cmd !== '' ? $cmd : '<none>') . PHP_EOL);

            if ($denyReason !== null) {
                fwrite(STDERR, "Reason: {$denyReason}" . PHP_EOL);
            }

            fwrite(STDERR, PHP_EOL);
            fwrite(STDERR, "Allowed in production (always): " . implode(', ', $allowAlways) . PHP_EOL);
            fwrite(STDERR, "Allowed in production (deploy only): " . implode(', ', $allowWhenDeploy) . PHP_EOL);

            if (!$isDeploy) {
                fwrite(STDERR, PHP_EOL);
                fwrite(STDERR, "To run deploy-only commands, set RADIX_DEPLOY=1 for this run." . PHP_EOL);
                fwrite(STDERR, "Example (Windows CMD): set RADIX_DEPLOY=1 && php radix migrations:migrate" . PHP_EOL);
                fwrite(STDERR, "Example (PowerShell): \$env:RADIX_DEPLOY='1'; php radix migrations:migrate; Remove-Item Env:RADIX_DEPLOY" . PHP_EOL);
            }

            exit(1);
        }
    }
}

if (getenv('APP_MAINTENANCE') === '1') {
    throw new \Radix\Http\Exception\MaintenanceException();
}

if (!$container instanceof \Psr\Container\ContainerInterface) {
    $type = is_object($container) ? get_class($container) : gettype($container);

    throw new RuntimeException(
        'Container verkar vara felaktig: ' . $type
    );
}

/** @var Radix\Session\RadixSessionHandler&SessionHandlerInterface $sessionHandler */
$sessionHandler = $container->get(Radix\Session\RadixSessionHandler::class);
session_set_save_handler($sessionHandler, true);

/** @var \Radix\Session\SessionInterface $session */
$session = $container->get(\Radix\Session\SessionInterface::class);

// Kontrollera om vi kör via CLI och om SESSION_DRIVER är database
if (php_sapi_name() === 'cli' && getenv('SESSION_DRIVER') === 'database') {
    /** @var \Radix\Database\Connection $db */
    $db = $container->get(\Radix\Database\Connection::class);

    try {
        // En extremt snabb koll om tabellen finns
        $db->execute("SELECT 1 FROM sessions LIMIT 1");
    } catch (\PDOException $e) {
        // Om tabellen inte finns (eller databasen är nere), stoppa med instruktioner
        echo "\n\033[31m[FEL] Systemet kan inte starta.\033[0m\n";
        echo "SESSION_DRIVER är satt till 'database' men tabellen 'sessions' saknas.\n\n";
        echo "Gör följande:\n";
        echo "1. Ändra till SESSION_DRIVER=file i din .env fil.\n";
        echo "2. Kör migrations för att skapa sessions-tabellen:\n";
        echo "   - Production: RADIX_DEPLOY=1 php radix migrations:migrate\n";
        echo "   - Development: php radix migrations:migrate\n";
        echo "3. Ändra tillbaka till SESSION_DRIVER=database om du önskar.\n\n";
        exit(1);
    }
}

$session->start();

setAppContainer($container);

// Ladda core providers + ev. scaffoldade providers (install = filen finns)
$providers = require ROOT_PATH . '/config/providers.php';
if (!is_array($providers)) {
    throw new RuntimeException('Config file config/providers.php must return an array.');
}

foreach (['auth', 'admin', 'contact'] as $preset) {
    $file = ROOT_PATH . '/config/providers.' . $preset . '.php';

    if (!is_file($file)) {
        continue;
    }

    /** @phpstan-ignore-next-line require.fileNotFound optional scaffolded config */
    $extra = require $file;

    if (!is_array($extra)) {
        throw new RuntimeException(sprintf(
            'Config file config/providers.%s.php must return an array.',
            $preset
        ));
    }

    $providers = array_values(array_unique(array_merge($providers, $extra)));
}

/**
 * @var array<int, class-string<\Radix\ServiceProvider\ServiceProviderInterface>> $providers
 */
foreach ($providers as $providerClass) {
    if (!is_string($providerClass)) {
        throw new RuntimeException('Varje provider-klass måste vara en sträng (class-string).');
    }

    $provider = $container->get($providerClass);

    if (!$provider instanceof \Radix\ServiceProvider\ServiceProviderInterface) {
        throw new RuntimeException(sprintf(
            'Provider "%s" implementerar inte ServiceProviderInterface.',
            $providerClass
        ));
    }

    $provider->register();
}

$router = require ROOT_PATH . '/config/routes.php';

$middleware = require ROOT_PATH . '/config/middleware.php';
if (!is_array($middleware)) {
    throw new RuntimeException('Config file config/middleware.php must return an array.');
}

// Ladda optional preset-middleware (install = filen finns)
foreach (['auth', 'admin', 'contact'] as $preset) {
    $file = ROOT_PATH . '/config/middleware.' . $preset . '.php';

    if (!is_file($file)) {
        continue;
    }

    /** @phpstan-ignore-next-line require.fileNotFound optional scaffolded config */
    $extra = require $file;

    if (!is_array($extra)) {
        throw new RuntimeException(sprintf(
            'Config file config/middleware.%s.php must return an array.',
            $preset
        ));
    }

    // Middleware är en flat map alias => class, så array_merge räcker
    $middleware = array_merge($middleware, $extra);
}

return $container;
