<?php

declare(strict_types=1);

$root = dirname(__DIR__);
$envFile = $root . DIRECTORY_SEPARATOR . '.env';
$exampleFile = $root . DIRECTORY_SEPARATOR . '.env.example';

function fail(string $message, int $code = 1): void
{
    fwrite(STDERR, $message . PHP_EOL);
    exit($code);
}

function randomHex(int $bytes): string
{
    return bin2hex(random_bytes($bytes));
}

$secretKeys = [
    'API_TOKEN' => 32,
    'SECURE_TOKEN_HMAC' => 32,
    'SECURE_ENCRYPTION_KEY' => 32,
];

if (!is_file($envFile)) {
    if (!is_file($exampleFile)) {
        fail('Missing .env.example');
    }
    if (!copy($exampleFile, $envFile)) {
        fail('Failed to copy .env.example to .env');
    }
}

$raw = file_get_contents($envFile);
if ($raw === false) {
    fail('Failed to read .env');
}

// Dela upp på rader (hanterar CRLF/LF)
$lines = preg_split("/\R/u", $raw);
if (!is_array($lines)) {
    fail('Failed to split .env into lines');
}

$generated = [];
$seen = array_fill_keys(array_keys($secretKeys), false);

foreach ($lines as $i => $line) {
    if (!is_string($line)) {
        continue;
    }

    // Hoppa över tomma rader och kommentarer
    $trim = ltrim($line);
    if ($trim === '' || str_starts_with($trim, '#')) {
        continue;
    }

    // Matcha KEY=VALUE (tillåt whitespace runt =, bevara ev. inline-suffix efter value)
    // Ex: API_TOKEN=   # comment
    if (preg_match('/^\s*([A-Z0-9_]+)\s*=\s*(.*)$/', $line, $m) !== 1) {
        continue;
    }

    $key = $m[1];
    if (!array_key_exists($key, $secretKeys)) {
        continue;
    }

    $seen[$key] = true;

    $rhs = (string) $m[2];

    // Separera ev inline-kommentar, men bara om det finns ett mellanslag före "#"
    // så vi inte råkar kapa värden som innehåller "#".
    $valuePart = $rhs;
    $suffix = '';
    if (preg_match('/^(.*?)\s(\#.*)$/', $rhs, $cm) === 1) {
        $valuePart = (string) $cm[1];
        $suffix = (string) $cm[2];
    }

    $valueTrimmed = trim($valuePart);
    $valueTrimmed = trim($valueTrimmed, "\"'");

    if ($valueTrimmed === '') {
        $newValue = randomHex($secretKeys[$key]);
        $lines[$i] = $key . '=' . $newValue . ($suffix !== '' ? ' ' . $suffix : '');
        $generated[] = $key;
    }
}

// Om nyckeln saknas helt i filen: lägg till längst ner
foreach ($secretKeys as $key => $bytes) {
    if (($seen[$key] ?? false) === false) {
        $newValue = randomHex($bytes);
        $lines[] = $key . '=' . $newValue;
        $generated[] = $key;
    }
}

$out = implode(PHP_EOL, $lines);
if (!str_ends_with($out, PHP_EOL)) {
    $out .= PHP_EOL;
}

if ($out !== $raw) {
    $backupFile = $envFile . '.bak';
    if (!is_file($backupFile)) {
        @copy($envFile, $backupFile);
    }

    if (file_put_contents($envFile, $out) === false) {
        fail('Failed to write .env');
    }
}

if ($generated !== []) {
    fwrite(STDOUT, 'Generated: ' . implode(', ', $generated) . PHP_EOL);
}
fwrite(STDOUT, "Env bootstrap completed.\n");
