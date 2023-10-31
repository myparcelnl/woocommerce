<?php

declare(strict_types=1);

use Isolated\Symfony\Component\Finder\Finder;

$mainConfig = require __DIR__ . '/scoper.inc.php';

/**
 * Scopes vendor dependencies.
 */
return array_replace($mainConfig, [
    'finders' => [
        Finder::create()
            ->files()
            ->ignoreVCS(true)
            ->notName('/LICENSE|.*\\.md|.*\\.dist|Makefile|composer\\.lock/')
            ->exclude([
                'test',
                'tests',
                'Tests',
                'vendor-bin',
            ])
            ->in('vendor'),
    ],

    'exclude-files' => [
        'vendor/php-di/php-di/src/Compiler/Template.php',
    ],
]);
