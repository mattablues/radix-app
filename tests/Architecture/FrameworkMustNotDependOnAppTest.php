<?php

declare(strict_types=1);

namespace Radix\Tests\Architecture;

use PHPUnit\Framework\TestCase;
use RecursiveDirectoryIterator;
use RecursiveIteratorIterator;
use SplFileInfo;

final class FrameworkMustNotDependOnAppTest extends TestCase
{
    private function skipIfDisabled(): void
    {
        $flag = getenv('SKIP_ARCH_TESTS');

        if ($flag === '1' || strtolower((string) $flag) === 'true') {
            self::markTestSkipped('Arkitekturtest tillfälligt avstängt (SKIP_ARCH_TESTS=1).');
        }
    }

    private function getInstalledFrameworkSrcPath(): ?string
    {
        // Kör som dependency: vendor/radix/framework/src
        $path = dirname(__DIR__, 2)
            . DIRECTORY_SEPARATOR . 'vendor'
            . DIRECTORY_SEPARATOR . 'radix'
            . DIRECTORY_SEPARATOR . 'framework'
            . DIRECTORY_SEPARATOR . 'src';

        return is_dir($path) ? $path : null;
    }

    public function testInstalledFrameworkSrcDoesNotReferenceAppNamespace(): void
    {
        $this->skipIfDisabled();

        $frameworkSrc = $this->getInstalledFrameworkSrcPath();
        if ($frameworkSrc === null) {
            self::markTestSkipped('radix/framework är inte installerat (vendor/radix/framework/src saknas).');
        }

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
            "Installerat framework får inte referera till App\\\\. Filer som bryter regeln:\n- " . implode("\n- ", $violations)
        );
    }

    public function testInstalledFrameworkSrcDoesNotReferenceAppOrAppHelpers(): void
    {
        $this->skipIfDisabled();

        $frameworkSrc = $this->getInstalledFrameworkSrcPath();
        if ($frameworkSrc === null) {
            self::markTestSkipped('radix/framework är inte installerat (vendor/radix/framework/src saknas).');
        }

        $rules = [
            'App namespace reference (App\\\...)' => '/\bApp\\\\/',
            'route(...) helper' => '/(?<!->)\broute\s*\(/',
            'view(...) helper' => '/^(?!.*\bfunction\s+view\s*\().*(?<!->)\bview\s*\(/m',
            'redirect(...) helper' => '/(?<!->)\bredirect\s*\(/',
            'asset(...) helper' => '/(?<!->)\basset\s*\(/',
            'dd(...) helper' => '/(?<!->)\bdd\s*\(/',
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
                "Installerat framework får inte bero på appen.\n\nRegelbrott:\n" . implode("\n", $lines)
            );
        }

        self::assertSame([], $violations, 'Inga regelbrott förväntas i installerat framework.');
    }
}
