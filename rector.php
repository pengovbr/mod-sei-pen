<?php
 
declare(strict_types=1);
 
use Rector\Config\RectorConfig;
use Rector\Set\ValueObject\LevelSetList;
 
return RectorConfig::configure()
    ->withPaths([
        __DIR__ . '/src',
    ])
 
    ->withPreparedSets(deadCode: true)
    ->withSets([LevelSetList::UP_TO_PHP_82])
    ->withTypeCoverageLevel(0);