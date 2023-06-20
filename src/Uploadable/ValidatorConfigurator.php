<?php

namespace Stof\DoctrineExtensionsBundle\Uploadable;

use Gedmo\Uploadable\Mapping\Validator;

/**
 * @internal
 */
class ValidatorConfigurator
{
    public function __construct(private readonly bool $validateWritableDirectory)
    {}

    public function configure(): void
    {
        Validator::$validateWritableDirectory = $this->validateWritableDirectory;
    }
}
