<?php

declare(strict_types=1);

use Gedmo\IpTraceable\IpTraceableListener;
use Stof\DoctrineExtensionsBundle\EventListener\IpTraceListener;
use Symfony\Component\DependencyInjection\Loader\Configurator\ContainerConfigurator;
use Symfony\Component\DependencyInjection\Loader\Configurator\ReferenceConfigurator;

return static function (ContainerConfigurator $containerConfigurator): void {

    $containerConfigurator->services()
        ->set('stof_doctrine_extensions.listener.ip_traceable', IpTraceableListener::class)
            ->call('setCacheItemPool', [new ReferenceConfigurator('stof_doctrine_extensions.metadata_cache')])
            ->call('setAnnotationReader', [(new ReferenceConfigurator('annotation_reader'))->ignoreOnInvalid()])

        ->set('stof_doctrine_extensions.event_listener.ip_trace', IpTraceListener::class)
            ->tag('kernel.event_subscriber')
            ->args([
                new ReferenceConfigurator('stof_doctrine_extensions.listener.ip_traceable'),
            ])
    ;
};