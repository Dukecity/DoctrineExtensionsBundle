<?php

namespace Stof\DoctrineExtensionsBundle\DependencyInjection;

use Symfony\Component\Cache\Adapter\ArrayAdapter;
use Symfony\Component\Config\Loader\LoaderInterface;
use Symfony\Component\DependencyInjection\Loader\PhpFileLoader;
use Symfony\Component\DependencyInjection\Alias;
use Symfony\Component\HttpKernel\DependencyInjection\Extension;
use Symfony\Component\Config\Definition\Processor;
use Symfony\Component\Config\FileLocator;
use Symfony\Component\DependencyInjection\ContainerBuilder;

class StofDoctrineExtensionsExtension extends Extension
{
    private const LISTENER_EVENTS = [
        'blameable' => [
            'prePersist',
            'onFlush',
            'loadClassMetadata',
        ],
        'ip_traceable' => [
            'prePersist',
            'onFlush',
            'loadClassMetadata',
        ],
        'loggable' => [
            'loadClassMetadata',
            'onFlush',
            'postPersist',
        ],
        'reference_integrity' => [
            'loadClassMetadata',
            'preRemove',
        ],
        'sluggable' => [
            'prePersist',
            'onFlush',
            'loadClassMetadata',
        ],
        'softdeleteable' => [
            'loadClassMetadata',
            'onFlush',
        ],
        'sortable' => [
            'onFlush',
            'loadClassMetadata',
            'prePersist',
            'postPersist',
            'preUpdate',
            'postRemove',
            'postFlush',
        ],
        'timestampable' => [
            'prePersist',
            'onFlush',
            'loadClassMetadata',
        ],
        'translatable' => [
            'postLoad',
            'postPersist',
            'preFlush',
            'onFlush',
            'loadClassMetadata',
        ],
        'tree' => [
            'prePersist',
            'preRemove',
            'preUpdate',
            'onFlush',
            'loadClassMetadata',
            'postPersist',
            'postUpdate',
            'postRemove',
        ],
        'uploadable' => [
            'loadClassMetadata',
            'preFlush',
            'onFlush',
            'postFlush',
        ],
    ];

    private array $entityManagers   = [];
    private array $documentManagers = [];

    /**
     * @throws \Exception
     */
    public function load(array $configs, ContainerBuilder $container): void
    {
        $processor = new Processor();
        $configuration = new Configuration();

        $config = $processor->processConfiguration($configuration, $configs);

        $loader = new PhpFileLoader($container, new FileLocator(__DIR__.'/../Resources/config'));

        $loaded = [];

        $this->entityManagers = $this->processObjectManagerConfigurations($config['orm'], $container, $loader, $loaded, 'doctrine.event_listener');
        $this->documentManagers = $this->processObjectManagerConfigurations($config['mongodb'], $container, $loader, $loaded, 'doctrine_mongodb.odm.event_listener');

        $container->setParameter('stof_doctrine_extensions.default_locale', $config['default_locale']);
        $container->setParameter('stof_doctrine_extensions.translation_fallback', $config['translation_fallback']);
        $container->setParameter('stof_doctrine_extensions.persist_default_translation', $config['persist_default_translation']);
        $container->setParameter('stof_doctrine_extensions.skip_translation_on_load', $config['skip_translation_on_load']);

        // Register the uploadable configuration if the listener is used
        if (isset($loaded['uploadable'])) {
            $uploadableConfig = $config['uploadable'];

            $container->setParameter('stof_doctrine_extensions.default_file_path', $uploadableConfig['default_file_path']);
            $container->setParameter('stof_doctrine_extensions.uploadable.default_file_info.class', $uploadableConfig['default_file_info_class']);
            $container->setParameter(
                'stof_doctrine_extensions.uploadable.validate_writable_directory',
                $uploadableConfig['validate_writable_directory']
            );

            if ($uploadableConfig['default_file_path']) {
                $container->getDefinition('stof_doctrine_extensions.listener.uploadable')
                    ->addMethodCall('setDefaultPath', [$uploadableConfig['default_file_path']]);
            }

            if ($uploadableConfig['mime_type_guesser_class']) {
                if (!class_exists($uploadableConfig['mime_type_guesser_class'])) {
                    $msg = 'Class "%s" configured to use as the mime type guesser in the Uploadable extension does not exist.';

                    throw new \InvalidArgumentException(sprintf($msg, $uploadableConfig['mime_type_guesser_class']));
                }

                $container->setParameter(
                    'stof_doctrine_extensions.uploadable.mime_type_guesser.class',
                    $uploadableConfig['mime_type_guesser_class']
                );
            }
        }

        if (isset($config['metadata_cache_pool'])) {
            $container->setAlias('stof_doctrine_extensions.metadata_cache', new Alias($config['metadata_cache_pool'], false));
        } else {
            $container->register('stof_doctrine_extensions.metadata_cache', ArrayAdapter::class)->setPublic(false);
        }

        foreach ($config['class'] as $listener => $class) {
            $container->setParameter(sprintf('stof_doctrine_extensions.listener.%s.class', $listener), $class);
        }
    }

    /**
     * @internal
     */
    public function configValidate(ContainerBuilder $container): void
    {
        foreach ($this->entityManagers as $name) {
            if (!$container->hasDefinition(sprintf('doctrine.dbal.%s_connection', $name))) {
                throw new \InvalidArgumentException(sprintf('Invalid %s config: DBAL connection "%s" not found', $this->getAlias(), $name));
            }
        }

        foreach ($this->documentManagers as $name) {
            if (!$container->hasDefinition(sprintf('doctrine_mongodb.odm.%s_document_manager', $name))) {
                throw new \InvalidArgumentException(sprintf('Invalid %s config: document manager "%s" not found', $this->getAlias(), $name));
            }
        }
    }

    /**
     * @param array            $configs
     * @param ContainerBuilder $container
     * @param LoaderInterface  $loader
     * @param array            $loaded
     * @param string           $doctrineListenerTag
     *
     * @return array
     * @throws \Exception
     */
    private function processObjectManagerConfigurations(array $configs, ContainerBuilder $container, LoaderInterface $loader, array &$loaded, string $doctrineListenerTag)
    {
        $usedManagers = [];

        $listenerPriorities = [
            'translatable' => -10,
            'loggable' => 5,
            'uploadable' => -5,
        ];

        foreach ($configs as $name => $listeners) {
            foreach ($listeners as $ext => $enabled) {
                if (!$enabled) {
                    continue;
                }

                if (!isset($loaded[$ext])) {
                    $loader->load($ext.'.php');
                    $loaded[$ext] = true;
                }

                $attributes = ['connection' => $name];

                if (isset($listenerPriorities[$ext])) {
                    $attributes['priority'] = $listenerPriorities[$ext];
                }

                $definition = $container->getDefinition(sprintf('stof_doctrine_extensions.listener.%s', $ext));

                foreach (self::LISTENER_EVENTS[$ext] as $event) {
                    $attributes['event'] = $event;
                    $definition->addTag($doctrineListenerTag, $attributes);
                }

                $usedManagers[$name] = true;
            }
        }

        return array_keys($usedManagers);
    }
}
