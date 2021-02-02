<?php

declare(strict_types=1);

use Rector\Core\Configuration\Option;
use Rector\Php74\Rector\Property\TypedPropertyRector;
use Rector\Set\ValueObject\SetList;
use Symfony\Component\DependencyInjection\Loader\Configurator\ContainerConfigurator;

return static function (ContainerConfigurator $containerConfigurator): void {
    // get parameters
    $parameters = $containerConfigurator->parameters();

    //    var_dump($parameters); die(Option::AUTOLOAD_PATHS);
    $parameters->set(Option::AUTOLOAD_PATHS, [
        './vendor/autoload.php',
//        __DIR__ . '/vendor/squizlabs/php_codesniffer/autoload.php',
        __DIR__ . '/config/setup.php',
//        './app/helpers/post-setup.php'
//		'./app', './app/lib'
    ]);


    // Define what rule sets will be applied
    $parameters->set(Option::SETS, [
        SetList::DEAD_CODE,
    ]);

    // get services (needed for register a single rule)
    // $services = $containerConfigurator->services();

    // register a single rule
    // $services->set(TypedPropertyRector::class);
};
