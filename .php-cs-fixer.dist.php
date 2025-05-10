<?php

require __DIR__ . '/vendor/autoload.php';

$finder = (new PhpCsFixer\Finder())
    ->in([
        __DIR__ . '/src',
        __DIR__ . '/tests',
    ])
;

return (new PhpCsFixer\Config())
    ->setRules([
        '@PhpCsFixer' => true,
        '@PSR12' => true,
    ])
    ->setFinder($finder)
;
