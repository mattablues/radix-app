<?php

declare(strict_types=1);

use Radix\Config\Config;
use Radix\Config\Dotenv;
use Radix\Console\Commands\MigrationCommand;
use Radix\Console\CommandsRegistry;
use Radix\Database\DatabaseManager;
use Radix\Database\Migration\Migrator;
use Radix\Mailer\MailManager;

// Ladda miljövariabler
$dotenv = new Dotenv(ROOT_PATH . '/.env', ROOT_PATH);
$dotenv->load();

// Skapa containern
$container = new Radix\Container\Container();
$container->add(\Psr\Container\ContainerInterface::class, $container);

$excludeFiles = ['services.php', 'routes.php', 'middleware.php', 'providers.php', 'listeners.php'];
$configFiles = glob(ROOT_PATH . '/config/*.php');
$configFiles = array_filter($configFiles, function ($file) use ($excludeFiles) {
    return !in_array(basename($file), $excludeFiles, true);
});

$configData = [];

// Sammanslå innehåll från alla andra konfigurationsfiler
foreach ($configFiles as $file) {
    $configData = array_merge_deep($configData, require $file);
}

// Registrera den sammanslagna konfigurationen i containern
$container->add('config', new Config($configData));

$container->addShared(\Radix\Database\Connection::class, function () use ($container) {
    /** @var Radix\Config\Config $config */
    $config = $container->get('config');
    $dbConfig = $config->get('database');

    // Validera att nödvändiga värden finns
    if (!$dbConfig['driver'] || !$dbConfig['database']) {
        throw new RuntimeException('Database configuration is invalid. Ensure "driver" and "database" are set.');
    }

    // Skapa DSN-strängen baserat på föraren
    $dsn = match ($dbConfig['driver']) {
        'sqlite' => "sqlite:{$dbConfig['database']}",
        'mysql' => sprintf(
            'mysql:host=%s;port=%s;dbname=%s;charset=%s',
            $dbConfig['host'],
            $dbConfig['port'],
            $dbConfig['database'],
            $dbConfig['charset']
        ),
        default => throw new RuntimeException("Unsupported database driver: {$dbConfig['driver']}"),
    };

    try {
        // Skapa en PDO-instans och återlämna en Connection
        $pdo = new PDO($dsn, $dbConfig['username'], $dbConfig['password'], $dbConfig['options']);

        return new Radix\Database\Connection($pdo);
    } catch (PDOException $e) {
        throw new RuntimeException("Failed to connect to the database: " . $e->getMessage(), $e->getCode(), $e);
    }
});

$container->addShared(\Radix\DateTime\RadixDateTime::class, function () use ($container) {
    $config = $container->get('config'); // Hämta config-instansen
    return new \Radix\DateTime\RadixDateTime($config); // Injicera config till RadixDateTime
});

$container->add(\Radix\Database\Migration\Migrator::class, function () use ($container) {
        $connection = $container->get(Radix\Database\Connection::class);
        $migrationsPath = ROOT_PATH . '/migrations';
        return new Migrator($connection, $migrationsPath);
});

$container->add(\Radix\Console\Commands\MigrationCommand::class, function () use ($container) {
    return new MigrationCommand($container->get(\Radix\Database\Migration\Migrator::class));
});

$container->add(\Radix\Console\Commands\MakeControllerCommand::class, function () {
    $controllerPath = ROOT_PATH . '/src/Controllers';
    $templatePath = ROOT_PATH . '/templates';

    return new \Radix\Console\Commands\MakeControllerCommand($controllerPath, $templatePath);
});

$container->add(\Radix\Console\Commands\MakeEventCommand::class, function () {
    $eventPath = ROOT_PATH . '/src/Events';
    $templatePath = ROOT_PATH . '/templates';

    return new \Radix\Console\Commands\MakeEventCommand($eventPath, $templatePath);
});

$container->add(\Radix\Console\Commands\MakeListenerCommand::class, function () {
    $listenerPath = ROOT_PATH . '/src/EventListeners';
    $templatePath = ROOT_PATH . '/templates';

    return new \Radix\Console\Commands\MakeListenerCommand($listenerPath, $templatePath);
});

$container->add(\Radix\Console\Commands\MakeMiddlewareCommand::class, function () {
    $middlewarePath = ROOT_PATH . '/src/Middlewares';
    $templatePath = ROOT_PATH . '/templates';

    return new \Radix\Console\Commands\MakeMiddlewareCommand($middlewarePath, $templatePath);
});

$container->add(\Radix\Console\Commands\MakeServiceCommand::class, function () {
    $servicePath = ROOT_PATH . '/src/Services';
    $templatePath = ROOT_PATH . '/templates';

    return new \Radix\Console\Commands\MakeServiceCommand($servicePath, $templatePath);
});

$container->add(\Radix\Console\Commands\MakeProviderCommand::class, function () {
    $providerPath = ROOT_PATH . '/src/Providers';
    $templatePath = ROOT_PATH . '/templates';

    return new \Radix\Console\Commands\MakeProviderCommand($providerPath, $templatePath);
});

$container->add(\Radix\Console\Commands\MakeMigrationCommand::class, function () {
    $migrationPath = ROOT_PATH . '/migrations';
    $templatePath = ROOT_PATH . '/templates/migrations';

    // Säkerställ att katalogerna är tillgängliga
    if (!is_dir($migrationPath)) {
        mkdir($migrationPath, 0755, true);
    }
    if (!is_dir($templatePath)) {
        mkdir($templatePath, 0755, true);
    }

    // Returnera en korrekt instans
    return new Radix\Console\Commands\MakeMigrationCommand($migrationPath, $templatePath);
});

$container->add(\Radix\Database\DatabaseManager::class, function () use ($container) {
    return new DatabaseManager($container);
});

$container->add(\Radix\Console\Commands\MakeModelCommand::class, function () {
    // Definiera paths för modeller och mallar
    $modelPath = ROOT_PATH . '/src/Models';
    $templatePath = ROOT_PATH . '/templates';

    // Kontrollera och skapa katalogerna om de inte existerar
    if (!is_dir($modelPath)) {
        mkdir($modelPath, 0755, true);
    }
    if (!is_dir($templatePath)) {
        mkdir($templatePath, 0755, true);
    }

    // Returnera instansen av MakeModelCommand med rätt beroenden
    return new Radix\Console\Commands\MakeModelCommand($modelPath, $templatePath);
});

$container->add(\Radix\Database\Migration\Schema::class, function () use ($container) {
    return new Radix\Database\Migration\Schema(
        $container->get(Radix\Database\Connection::class)
    );
});

$container->add(\Radix\Console\CommandsRegistry::class, function () {
    $registry = new CommandsRegistry();

    // Registrera alla CLI-kommandon med det nya namnsystemet
    $registry->register('migrations:migrate', Radix\Console\Commands\MigrationCommand::class);
    $registry->register('migrations:rollback', Radix\Console\Commands\MigrationCommand::class);
    $registry->register('make:migration', Radix\Console\Commands\MakeMigrationCommand::class);
    $registry->register('make:model', Radix\Console\Commands\MakeModelCommand::class); // Nytt kommando
    $registry->register('make:controller', Radix\Console\Commands\MakeControllerCommand::class);
    $registry->register('make:event', Radix\Console\Commands\MakeEventCommand::class);
    $registry->register('make:listener', Radix\Console\Commands\MakeListenerCommand::class);
    $registry->register('make:middleware', Radix\Console\Commands\MakeMiddlewareCommand::class);
    $registry->register('make:service', Radix\Console\Commands\MakeServiceCommand::class);
    $registry->register('make:provider', Radix\Console\Commands\MakeProviderCommand::class);

    return $registry;
});

$container->addShared(\Radix\Session\RadixSessionHandler::class, function () use ($container) {
    $dbConnection = null;
    try {
        $dbConnection = $container->get(Radix\Database\Connection::class)->getPDO();
    } catch (Throwable $e) {
        error_log("Kunde inte hämta PDO: " . $e->getMessage());
    }

    $config = $container->get('config');

    return new Radix\Session\RadixSessionHandler($config->get('session'), $dbConnection);
});

$container->addShared(\Radix\Session\SessionInterface::class, \Radix\Session\Session::class);

$container->add(\Radix\Http\Response::class);
$container->add(\Radix\Http\Request::class);
$container->add(\Radix\Routing\Router::class);

$container->addShared(\Radix\Viewer\TemplateViewerInterface::class, function () use($container) {
    $session = $container->get(\Radix\Session\SessionInterface::class);
    $datetime = $container->get(\Radix\DateTime\RadixDateTime::class); // Hämta den delade RadixDateTime-instansen
    $viewer = new \Radix\Viewer\RadixTemplateViewer();
    $viewer->enableDebugMode(getenv('APP_DEBUG') === '1');

    // Lägg till delade variabler
    $viewer->shared('datetime', $datetime); // Gör datetime tillgänglig i alla vyer
    $viewer->shared('session', $session);

    $userId = $session->get(\Radix\Session\Session::AUTH_KEY);
    
    if ($userId && ($user = \App\Models\User::with(['status', 'token'])
            ->where('id', '=', $userId)
            ->first())
    ) {
        $viewer->shared('currentUser', $user); // Gör currentUser tillgänglig i alla vyer
        $viewer->shared('currentToken', $user->getRelation('token')->getAttribute('value') ?? null); // Gör currentStatus tillgänglig i alla vyer
    }

    return $viewer;
});

$container->addShared(\Radix\EventDispatcher\EventDispatcher::class, \Radix\EventDispatcher\EventDispatcher::class);

$container->add(\Radix\Mailer\MailManager::class, function () use ($container) {
    $templateViewer = $container->get(\Radix\Viewer\TemplateViewerInterface::class);
    $config = $container->get('config'); // Rätt instans av Config

    return MailManager::createDefault($templateViewer, $config); // Skickar in rätt argument
});

return $container;