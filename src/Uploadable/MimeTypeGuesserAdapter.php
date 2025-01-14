<?php

namespace Stof\DoctrineExtensionsBundle\Uploadable;

use Gedmo\Uploadable\MimeType\MimeTypeGuesserInterface;
use Symfony\Component\Mime\MimeTypes;

class MimeTypeGuesserAdapter implements MimeTypeGuesserInterface
{
    /**
     * @param string $filePath
     */
    public function guess($filePath): ?string
    {
        return MimeTypes::getDefault()->guessMimeType($filePath);
    }
}
