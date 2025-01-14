<?php

namespace Stof\DoctrineExtensionsBundle\DependencyInjection\Compiler;

use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Compiler\CompilerPassInterface;

/**
 * @internal
 */
class ValidateExtensionConfigurationPass implements CompilerPassInterface
{
    /**
     * Validate the DoctrineExtensions DIC extension config.
     *
     * This validation runs in a discrete compiler pass because it depends on
     * DBAL and ODM services, which aren't available during the config merge
     * compiler pass.
     */
    public function process(ContainerBuilder $container): void
    {
        $container->getExtension('stof_doctrine_extensions')->configValidate($container);
    }
}
