<?php

namespace Stof\DoctrineExtensionsBundle\Tests\DependencyInjection;

use Doctrine\Common\Annotations\AnnotationReader;
use Doctrine\Common\EventSubscriber;
use Stof\DoctrineExtensionsBundle\DependencyInjection\StofDoctrineExtensionsExtension;
use PHPUnit\Framework\TestCase;
use Symfony\Component\DependencyInjection\ContainerBuilder;

class StofDoctrineExtensionsExtensionTest extends TestCase
{
    /**
     * @return string[]
     */
    public static function provideExtensions(): array
    {
        return [
            ['blameable'],
            ['ip_traceable'],
            ['loggable'],
            ['reference_integrity'],
            ['sluggable'],
            ['softdeleteable'],
            ['sortable'],
            ['timestampable'],
            ['translatable'],
            ['tree'],
            ['uploadable'],
        ];
    }

    /**
     * @dataProvider provideExtensions
     */
    public function testLoadORMConfig(string $listener): void
    {
        $extension = new StofDoctrineExtensionsExtension();
        $container = new ContainerBuilder();

        $config = ['orm' => [
            'default' => [$listener => true],
            'other' => [$listener => true],
        ]];

        $extension->load([$config], $container);

        $this->assertTrue($container->hasDefinition('stof_doctrine_extensions.listener.'.$listener));

        $def = $container->getDefinition('stof_doctrine_extensions.listener.'.$listener);

        $this->assertTrue($def->hasTag('doctrine.event_listener'));

        $tags = $def->getTag('doctrine.event_listener');
        $configuredManagers = array_unique(array_column($tags, 'connection'));

        $this->assertCount(2, $configuredManagers);
    }

    /**
     * @dataProvider provideExtensions
     */
    public function testLoadMongodbConfig(string $listener): void
    {
        $extension = new StofDoctrineExtensionsExtension();
        $container = new ContainerBuilder();

        $config = ['mongodb' => [
            'default' => [$listener => true],
            'other' => [$listener => true],
        ]];

        $extension->load([$config], $container);

        $this->assertTrue($container->hasDefinition('stof_doctrine_extensions.listener.'.$listener));

        $def = $container->getDefinition('stof_doctrine_extensions.listener.'.$listener);

        $this->assertTrue($def->hasTag('doctrine_mongodb.odm.event_listener'));

        $tags = $def->getTag('doctrine_mongodb.odm.event_listener');
        $configuredManagers = array_unique(array_column($tags, 'connection'));

        $this->assertCount(2, $configuredManagers);
    }

    /**
     * @dataProvider provideExtensions
     */
    public function testLoadBothConfig(string $listener): void
    {
        $extension = new StofDoctrineExtensionsExtension();
        $container = new ContainerBuilder();

        $config = [
            'orm' => ['default' => [$listener => true]],
            'mongodb' => ['default' => [$listener => true]],
        ];

        $extension->load([$config], $container);

        $this->assertTrue($container->hasDefinition('stof_doctrine_extensions.listener.'.$listener));

        $def = $container->getDefinition('stof_doctrine_extensions.listener.'.$listener);

        $this->assertTrue($def->hasTag('doctrine.event_listener'));
        $this->assertTrue($def->hasTag('doctrine_mongodb.odm.event_listener'));

        $this->assertCount(1, array_unique(array_column($def->getTag('doctrine.event_listener'), 'connection')));
        $this->assertCount(1, array_unique(array_column($def->getTag('doctrine_mongodb.odm.event_listener'), 'connection')));
    }

    /**
     * @dataProvider provideExtensions
     */
    public function testEventConsistency(string $listener)
    {
        $extension = new StofDoctrineExtensionsExtension();
        $container = new ContainerBuilder();
        $container->register('annotation_reader', AnnotationReader::class);

        $config = array('orm' => array(
            'default' => array($listener => true),
        ));

        $this->assertCount(1, $def->getTag('doctrine.event_subscriber'));
        $this->assertCount(1, $def->getTag('doctrine_mongodb.odm.event_subscriber'));
    }

    /**
     * @dataProvider provideExtensions
     */
    public function testEventConsistency(string $listener)
    {
        $extension = new StofDoctrineExtensionsExtension();
        $container = new ContainerBuilder();
        $container->register('annotation_reader', AnnotationReader::class);

        $config = array('orm' => array(
            'default' => array($listener => true),
        ));

        $extension->load(array($config), $container);

        $def = $container->getDefinition('stof_doctrine_extensions.listener.'.$listener);
        $configuredEvents = array_column($def->getTag('doctrine.event_listener'), 'event');

        $listenerInstance = $container->get('stof_doctrine_extensions.listener.'.$listener);

        if (!$listenerInstance instanceof EventSubscriber) {
            $this->markTestSkipped(sprintf('The listener for "%s" is not a Doctrine event subscriber.', $listener));
        }

        $this->assertEqualsCanonicalizing($listenerInstance->getSubscribedEvents(), $configuredEvents);
    }

    public function testLoadsDefaultCache(): void
    {
        $extension = new StofDoctrineExtensionsExtension();
        $container = new ContainerBuilder();

        $extension->load([], $container);

        $this->assertTrue($container->hasAlias('stof_doctrine_extensions.cache.pool.default'));
        $this->assertSame(
            'stof_doctrine_extensions.cache.pool.array',
            (string) $container->getAlias('stof_doctrine_extensions.cache.pool.default')
        );
    }

    public function testSettingCustomCache(): void
    {
        $extension = new StofDoctrineExtensionsExtension();
        $container = new ContainerBuilder();

        $extension->load([
            'custom_cache' => ['metadata_cache_pool' => 'my_custom_service_cache'],
        ], $container);

        $this->assertTrue($container->hasAlias('stof_doctrine_extensions.cache.pool.default'));
        $this->assertSame(
            'my_custom_service_cache',
            (string) $container->getAlias('stof_doctrine_extensions.cache.pool.default')
        );
    }
}
