<?php

$finder = (new PhpCsFixer\Finder())
    ->in([
        __DIR__ . '/src',
        __DIR__ . '/tests',
        __DIR__ . '/config',
        __DIR__ . '/routes',
        __DIR__ . '/support',
        __DIR__ . '/database',
        __DIR__ . '/templates/scaffolds',
        // Lägg till fler mappar vid behov, t.ex. 'tools' om du vill städa dem
    ])
    ->exclude([
        'views', // scaffold-views (t.ex. ratio-stubs) är templates, inte PHP
    ])
    ->name('*.php')
    ->name('*.php.stub')
    ->notName('*.ratio.php.stub')
    ->ignoreDotFiles(true)
    ->ignoreVCS(true);

return (new PhpCsFixer\Config())
    ->setRules([
        '@PER-CS' => true, // Den moderna efterföljaren till PSR-12
        '@PHP82Migration' => true, // Eller @PHP83Migration beroende på din version
        'array_syntax' => ['syntax' => 'short'],
        'ordered_imports' => ['sort_algorithm' => 'alpha'],
        'no_unused_imports' => true,
        'global_namespace_import' => [
            'import_classes' => true,
            'import_constants' => true,
            'import_functions' => true,
        ],
    ])
    ->setFinder($finder);
