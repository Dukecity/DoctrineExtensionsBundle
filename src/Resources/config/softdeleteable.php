<?php

declare(strict_types=1);

use Gedmo\SoftDeleteable\SoftDeleteableListener;
use Symfony\Component\DependencyInjection\Loader\Configurator\ContainerConfigurator;
use Symfony\Component\DependencyInjection\Loader\Configurator\ReferenceConfigurator;

return static function (ContainerConfigurator $containerConfigurator): void {
    $containerConfigurator->parameters()
        ->set('stof_doctrine_extensions.listener.softdeleteable.class', SoftDeleteableListener::class)
    ;

    $containerConfigurator->services()
        ->set('stof_doctrine_extensions.listener.softdeleteable', '%stof_doctrine_extensions.listener.softdeleteable.class%')
            ->call('setCacheItemPool', [new ReferenceConfigurator('stof_doctrine_extensions.metadata_cache')])
            ->call('setAnnotationReader', [(new ReferenceConfigurator('annotation_reader'))->ignoreOnInvalid()]);
};
