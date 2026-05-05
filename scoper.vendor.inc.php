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

    'patchers' => [
        static function (string $filePath, string $prefix, string $contents): string {
            if ($filePath !== 'vendor/php-di/php-di/src/functions.php') {
                return $contents;
            }

            // php-scoper rewrites the namespace declaration but not string literals inside
            // function_exists() calls. Without this patch, if another plugin (e.g. ActiveCampaign
            // for WooCommerce) loads DI\value() first, our guard sees it as already defined and
            // skips defining _MyParcelNL\DI\value() — causing a fatal error.
            return str_replace(
                "function_exists('DI\\",
                "function_exists('{$prefix}\\DI\\",
                $contents
            );
        },
    ],
]);
