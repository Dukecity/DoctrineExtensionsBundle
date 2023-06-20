<?php

namespace Stof\DoctrineExtensionsBundle\Uploadable;

use Gedmo\Uploadable\FileInfo\FileInfoInterface;
use Symfony\Component\HttpFoundation\File\UploadedFile;

class UploadedFileInfo implements FileInfoInterface
{
    public function __construct(private readonly UploadedFile $uploadedFile)
    {
    }

    public function getTmpName(): ?string
    {
        return $this->uploadedFile->getPathname();
    }

    public function getName(): ?string
    {
        return $this->uploadedFile->getClientOriginalName();
    }

    public function getSize(): ?string
    {
        return $this->uploadedFile->getSize();
    }

    public function getType(): ?string
    {
        return $this->uploadedFile->getMimeType();
    }

    public function getError(): int
    {
        return $this->uploadedFile->getError();
    }

    /**
     * {@inheritDoc}
     */
    public function isUploadedFile(): bool
    {
        return is_uploaded_file($this->uploadedFile->getPathname());
    }
}
