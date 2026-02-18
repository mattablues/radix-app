<?php

declare(strict_types=1);

/**
 * Kör PHPStan med rätt config och undviker stub-krockar:
 * - Om vissa optional klasser saknas i src/, generera en temporär stub-fil med ENDAST de klasserna
 *   och kör PHPStan med en temporär neon som scanFiles:ar den filen.
 * - Om inget saknas -> kör phpstan.neon.dist direkt.
 *
 * Du kan tvinga profil med env:
 *   PHPSTAN_PROFILE=minimal  eller  PHPSTAN_PROFILE=full
 */

$root = dirname(__DIR__);

$profile = getenv('PHPSTAN_PROFILE');
$profile = is_string($profile) ? strtolower(trim($profile)) : '';

$fullConfig = $root . DIRECTORY_SEPARATOR . 'phpstan.neon.dist';

if (!is_file($fullConfig)) {
    fwrite(STDERR, "PHPStan config not found: {$fullConfig}\n");
    exit(2);
}

$optional = [
    // Services
    'App\\Services\\AuthService' => $root . DIRECTORY_SEPARATOR . 'src' . DIRECTORY_SEPARATOR . 'Services' . DIRECTORY_SEPARATOR . 'AuthService.php',
    'App\\Services\\HealthCheckService' => $root . DIRECTORY_SEPARATOR . 'src' . DIRECTORY_SEPARATOR . 'Services' . DIRECTORY_SEPARATOR . 'HealthCheckService.php',
    'App\\Services\\ProfileAvatarService' => $root . DIRECTORY_SEPARATOR . 'src' . DIRECTORY_SEPARATOR . 'Services' . DIRECTORY_SEPARATOR . 'ProfileAvatarService.php',

    // Models
    'App\\Models\\SystemUpdate' => $root . DIRECTORY_SEPARATOR . 'src' . DIRECTORY_SEPARATOR . 'Models' . DIRECTORY_SEPARATOR . 'SystemUpdate.php',
];

$missing = [];
foreach ($optional as $fqcn => $path) {
    if (!is_file($path)) {
        $missing[] = $fqcn;
    }
}

/**
 * Tvingat läge:
 * - full: kör alltid fullConfig utan stubs
 * - minimal: generera stubs för ALLA optional-klasser som saknas (vilket vi redan gör), men även om
 *   inget saknas kör vi ändå fullConfig (det spelar ingen roll).
 */
$forceFull = ($profile === 'full');
$forceMinimal = ($profile === 'minimal');

$tmpStubFile = null;
$tmpNeonFile = null;

$configToUse = $fullConfig;

if (!$forceFull) {
    if ($missing !== []) {
        $tmpDir = rtrim(sys_get_temp_dir(), "/\\");
        $tmpStubFile = $tmpDir . DIRECTORY_SEPARATOR . 'phpstan-optional-stubs-' . uniqid('', true) . '.php';
        $tmpNeonFile = $tmpDir . DIRECTORY_SEPARATOR . 'phpstan-optional-config-' . uniqid('', true) . '.neon';

        $stubPhp = "<?php\n\ndeclare(strict_types=1);\n\n";

        // --- App\\Models\\SystemUpdate ---
        if (in_array('App\\Models\\SystemUpdate', $missing, true)) {
            $stubPhp .= "namespace App\\Models;\n\n";
            $stubPhp .= "class SystemUpdate\n{\n";
            $stubPhp .= "    public static function orderBy(string \$column, string \$direction): self {}\n\n";
            $stubPhp .= "    public function limit(int \$limit): self {}\n\n";
            $stubPhp .= "    /** @return array<int, mixed> */\n";
            $stubPhp .= "    public function get(): array {}\n\n";
            $stubPhp .= "    /** @return array<int, mixed> */\n";
            $stubPhp .= "    public function pluck(string \$column): array {}\n\n";
            $stubPhp .= "    public function first(): mixed {}\n";
            $stubPhp .= "}\n\n";
        }

        // --- App\\Services\\... ---
        $needAnyService =
            in_array('App\\Services\\AuthService', $missing, true)
            || in_array('App\\Services\\HealthCheckService', $missing, true)
            || in_array('App\\Services\\ProfileAvatarService', $missing, true);

        if ($needAnyService) {
            $stubPhp .= "namespace App\\Services;\n\n";
            $stubPhp .= "use App\\Models\\User;\n\n";

            if (in_array('App\\Services\\HealthCheckService', $missing, true)) {
                $stubPhp .= "final class HealthCheckService\n{\n";
                $stubPhp .= "    public function __construct(mixed \$logger) {}\n\n";
                $stubPhp .= "    /** @return array<string, string|bool> */\n";
                $stubPhp .= "    public function run(): array {}\n";
                $stubPhp .= "}\n\n";
            }

            if (in_array('App\\Services\\ProfileAvatarService', $missing, true)) {
                $stubPhp .= "final class ProfileAvatarService\n{\n";
                $stubPhp .= "    public function __construct(mixed \$uploadService) {}\n\n";
                $stubPhp .= "    /**\n";
                $stubPhp .= "     * @param int|string \$userId\n";
                $stubPhp .= "     * @param array{error:int,name?:string,tmp_name?:string,size?:int,type?:string}|null \$avatar\n";
                $stubPhp .= "     */\n";
                $stubPhp .= "    public function updateAvatar(User \$user, int|string \$userId, ?array \$avatar): void {}\n";
                $stubPhp .= "}\n\n";
            }

            if (in_array('App\\Services\\AuthService', $missing, true)) {
                $stubPhp .= "final class AuthService\n{\n";
                $stubPhp .= "    public function __construct(mixed \$session) {}\n\n";
                $stubPhp .= "    public function isBlocked(string \$email): bool {}\n";
                $stubPhp .= "    public function getBlockedUntil(string \$email): ?int {}\n";
                $stubPhp .= "    public function isIpBlocked(string \$ip): bool {}\n";
                $stubPhp .= "    public function getBlockedIpUntil(string \$ip): ?int {}\n";
                $stubPhp .= "    public function clearFailedAttempts(string \$email, bool \$removeBlocked = true): void {}\n";
                $stubPhp .= "    public function clearFailedIpAttempt(string \$ip): void {}\n";
                $stubPhp .= "    public function trackFailedAttempt(string \$email): void {}\n";
                $stubPhp .= "    public function trackFailedIpAttempt(string \$ip): void {}\n";
                $stubPhp .= "    /** @param array{email:string,password:string} \$data */\n";
                $stubPhp .= "    public function login(array \$data): ?User {}\n";
                $stubPhp .= "    public function getStatusError(?User \$user): ?string {}\n";
                $stubPhp .= "}\n\n";
            }
        }

        file_put_contents($tmpStubFile, $stubPhp);

        $neon = "includes:\n";
        $neon .= "    - " . str_replace('\\', '/', $fullConfig) . "\n\n";
        $neon .= "parameters:\n";
        $neon .= "    scanFiles:\n";
        $neon .= "        - " . str_replace('\\', '/', $tmpStubFile) . "\n";

        file_put_contents($tmpNeonFile, $neon);

        $configToUse = $tmpNeonFile;
    } elseif ($forceMinimal) {
        // minimal men inget saknas -> kör full ändå (ingen skillnad)
        $configToUse = $fullConfig;
    }
}

// Hitta phpstan bin (Windows: .bat)
$phpstanBin = $root . DIRECTORY_SEPARATOR . 'vendor' . DIRECTORY_SEPARATOR . 'bin' . DIRECTORY_SEPARATOR . 'phpstan';
if (!is_file($phpstanBin)) {
    $phpstanBinBat = $phpstanBin . '.bat';
    if (is_file($phpstanBinBat)) {
        $phpstanBin = $phpstanBinBat;
    }
}

// Bygg kommando: behåll ev extra args som du skickar efter scriptet
$args = $argv;
array_shift($args);

$cmdParts = [];
$cmdParts[] = escapeshellarg(PHP_BINARY);
$cmdParts[] = escapeshellarg($phpstanBin);
$cmdParts[] = 'analyse';
$cmdParts[] = '-c';
$cmdParts[] = escapeshellarg($configToUse);

foreach ($args as $a) {
    $cmdParts[] = escapeshellarg($a);
}

$cmd = implode(' ', $cmdParts);
passthru($cmd, $exitCode);

// Städa temporära filer
if (is_string($tmpStubFile) && is_file($tmpStubFile)) {
    @unlink($tmpStubFile);
}
if (is_string($tmpNeonFile) && is_file($tmpNeonFile)) {
    @unlink($tmpNeonFile);
}

exit((int) $exitCode);
