<?php

declare(strict_types=1);

namespace Radix\Tests\Architecture;

use PHPUnit\Framework\TestCase;
use RecursiveDirectoryIterator;
use RecursiveIteratorIterator;
use SplFileInfo;

/**
 * Arkitekturspärr: framework får inte referera till App\...
 *
 * Målet är att kunna lyfta ut /framework som egen repo senare utan beroenden till appen.
 */
final class FrameworkMustNotDependOnAppTest extends TestCase
{
    private function skipIfDisabled(): void
    {
        $flag = getenv('SKIP_ARCH_TESTS');

        if ($flag === '1' || strtolower((string) $flag) === 'true') {
            self::markTestSkipped('Arkitekturtest tillfälligt avstängt (SKIP_ARCH_TESTS=1).');
        }
    }

    public function testFrameworkSrcDoesNotReferenceAppNamespace(): void
    {
        $this->skipIfDisabled();
        // ... existing code ...

        $frameworkSrc = dirname(__DIR__, 2) . DIRECTORY_SEPARATOR . 'framework' . DIRECTORY_SEPARATOR . 'src';
        self::assertDirectoryExists($frameworkSrc, 'Hittar inte framework/src: ' . $frameworkSrc);

        $violations = [];

        $it = new RecursiveIteratorIterator(
            new RecursiveDirectoryIterator($frameworkSrc, RecursiveDirectoryIterator::SKIP_DOTS)
        );

        /** @var SplFileInfo $file */
        foreach ($it as $file) {
            if (!$file->isFile()) {
                continue;
            }

            if (strtolower($file->getExtension()) !== 'php') {
                continue;
            }

            $path = $file->getPathname();
            $contents = @file_get_contents($path);
            if ($contents === false) {
                continue;
            }

            if (preg_match('/\bApp\\\\/', $contents) === 1) {
                $violations[] = $path;
            }
        }

        self::assertSame(
            [],
            $violations,
            "Framework får inte referera till App\\\\. Filer som bryter regeln:\n- " . implode("\n- ", $violations)
        );

    }

    public function testFrameworkSrcDoesNotReferenceAppOrAppHelpers(): void
    {
        $this->skipIfDisabled();
        // ... existing code ...

        $frameworkSrc = dirname(__DIR__, 2) . DIRECTORY_SEPARATOR . 'framework' . DIRECTORY_SEPARATOR . 'src';
        self::assertDirectoryExists($frameworkSrc, 'Hittar inte framework/src: ' . $frameworkSrc);

        $rules = [
            'App namespace reference (App\\\...)' => '/\bApp\\\\/',
            'route(...) helper' => '/(?<!->)\broute\s*\(/',

            // Matcha "view(" endast om raden INTE innehåller "function view(" och det inte är "->view("
            'view(...) helper' => '/^(?!.*\bfunction\s+view\s*\().*(?<!->)\bview\s*\(/m',

            'redirect(...) helper' => '/(?<!->)\bredirect\s*\(/',
            'asset(...) helper' => '/(?<!->)\basset\s*\(/',
            'dd(...) helper' => '/(?<!->)\bdd\s*\(/',

            // Matcha "dump(" endast om raden INTE innehåller "function dump(" eller docblock "@method ... dump("
            // och det inte är "->dump("
            'dump(...) helper' => '/^(?!.*\bfunction\s+dump\s*\()(?!.*@method[^\n]*\bdump\s*\().*(?<!->)\bdump\s*\(/m',

            'env(...) helper (app-style)' => '/(?<!->)\benv\s*\(/',
            'config(...) helper (app-style)' => '/(?<!->)\bconfig\s*\(/',
        ];

        /** @var array<string, array<int, string>> $violations */
        $violations = [];

        $it = new RecursiveIteratorIterator(
            new RecursiveDirectoryIterator($frameworkSrc, RecursiveDirectoryIterator::SKIP_DOTS)
        );

        /** @var SplFileInfo $file */
        foreach ($it as $file) {
            if (!$file->isFile()) {
                continue;
            }

            if (strtolower($file->getExtension()) !== 'php') {
                continue;
            }

            $path = $file->getPathname();
            $contents = @file_get_contents($path);
            if ($contents === false) {
                continue;
            }

            foreach ($rules as $label => $pattern) {
                if (preg_match($pattern, $contents) === 1) {
                    $violations[$label][] = $path;
                }
            }
        }

        if ($violations !== []) {
            $lines = [];
            foreach ($violations as $label => $files) {
                $files = array_values(array_unique($files));
                sort($files);
                $lines[] = $label . ':';
                foreach ($files as $p) {
                    $lines[] = '  - ' . $p;
                }
            }

            self::fail(
                "Framework får inte bero på appen.\n\nRegelbrott:\n" . implode("\n", $lines)
            );
        }
    }
}
