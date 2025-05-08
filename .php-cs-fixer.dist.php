<?php

declare(strict_types=1);

require_once 'vendor/autoload.php';

return \Spiral\CodeStyle\Builder::create()
    ->include(__DIR__ . '/src')
    ->include(__DIR__ . '/bin')
    ->include(__DIR__ . '/config')
    ->include(__DIR__ . '/tests')
    ->include(__DIR__ . '/rector.php')
    ->allowRisky(false)
    ->build();
