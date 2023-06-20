<?php

namespace Stof\DoctrineExtensionsBundle\Tests\DependencyInjection;

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

        $this->assertTrue($def->hasTag('doctrine.event_subscriber'));

        $tags = $def->getTag('doctrine.event_subscriber');

        $this->assertCount(2, $tags);
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

        $this->assertTrue($def->hasTag('doctrine_mongodb.odm.event_subscriber'));

        $tags = $def->getTag('doctrine_mongodb.odm.event_subscriber');

        $this->assertCount(2, $tags);
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

        $this->assertTrue($def->hasTag('doctrine.event_subscriber'));
        $this->assertTrue($def->hasTag('doctrine_mongodb.odm.event_subscriber'));

        $this->assertCount(1, $def->getTag('doctrine.event_subscriber'));
        $this->assertCount(1, $def->getTag('doctrine_mongodb.odm.event_subscriber'));
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

    public function testConfigWithCustomListenerPriorities(): void
    {
        $extension = new StofDoctrineExtensionsExtension();
        $container = new ContainerBuilder();

        $config = [
            'orm' => ['default' => [
                'blameable' => 0,
                'loggable' => -1,
                'uploadable' => true,
                'sortable' => false,
            ]],
            'mongodb' => ['default' => [
                'blameable' => 0,
                'loggable' => -1,
                'uploadable' => true,
                'sortable' => false,
            ]],
        ];

        $extension->load([$config], $container);

        # blameable
        $def = $container->getDefinition('stof_doctrine_extensions.listener.blameable');
        $this->assertTrue($def->hasTag('doctrine.event_subscriber'));

        $this->assertSame(0, $def->getTag('doctrine.event_subscriber')[0]['priority']);

        # loggable
        $def = $container->getDefinition('stof_doctrine_extensions.listener.loggable');
        $this->assertTrue($def->hasTag('doctrine.event_subscriber'));
        $this->assertSame(-1, $def->getTag('doctrine.event_subscriber')[0]['priority']);

        # uploadable
        $def = $container->getDefinition('stof_doctrine_extensions.listener.uploadable');
        $this->assertTrue($def->hasTag('doctrine.event_subscriber'));
        $this->assertSame(-5, $def->getTag('doctrine.event_subscriber')[0]['priority']);

        $this->assertFalse($container->hasDefinition('stof_doctrine_extensions.listener.sortable'));
    }
}
