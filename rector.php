<?php

declare(strict_types=1);

use Rector\Config\RectorConfig;
use Rector\Php80\Rector\Catch_\RemoveUnusedVariableInCatchRector;
use Rector\Php80\Rector\Class_\ClassPropertyAssignToConstructorPromotionRector;
use Rector\Php81\Rector\FuncCall\NullToStrictStringFuncCallArgRector;
use Rector\Php81\Rector\Property\ReadOnlyPropertyRector;
use Rector\Php82\Rector\Class_\ReadOnlyClassRector;

return RectorConfig::configure()
    ->withPaths([
        __DIR__ . '/po',
        __DIR__ . '/Store',
    ])
    // uncomment to reach your current PHP version
    ->withPhpSets(php82: true)
    ->withRules([
    ])
    ->withSkip([
        ClassPropertyAssignToConstructorPromotionRector::class,
        NullToStrictStringFuncCallArgRector::class,
        RemoveUnusedVariableInCatchRector::class,
        ReadOnlyPropertyRector::class,
        ReadOnlyClassRector::class,
    ])
    ->withTypeCoverageLevel(1)
    ->withDeadCodeLevel(1);
