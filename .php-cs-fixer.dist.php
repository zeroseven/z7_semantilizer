<?php

declare(strict_types=1);

use PhpCsFixer\Config;
use PhpCsFixer\Finder;

$finder = Finder::create()
    ->in(__DIR__)
    ->exclude(['Build', '.build', 'config', 'vendor', 'node_modules', '.ddev', '.phpunit.cache', 'public', 'var'])
    ->name('*.php')
    ->notPath('ext_emconf.php');

return (new Config())
    ->setRiskyAllowed(true)
    ->setRules([
        '@PER-CS2.0' => true,
        'declare_strict_types' => true,
    ])
    ->setFinder($finder);
