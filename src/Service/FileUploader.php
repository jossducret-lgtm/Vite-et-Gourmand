<?php

namespace App\Service;

use Symfony\Component\HttpFoundation\File\Exception\FileException;
use Symfony\Component\HttpFoundation\File\UploadedFile;
use Symfony\Component\String\Slugger\SluggerInterface;

final class FileUploader
{
    public function __construct(
        private readonly string $targetDirectory,
        private readonly SluggerInterface $slugger,
    ) {
    }

    public function upload(UploadedFile $file, string $prefix = 'file'): string
    {
        $originalFilename = pathinfo($file->getClientOriginalName(), PATHINFO_FILENAME);
        $safeFilename = $this->slugger->slug($originalFilename)->lower();
        $fileName = sprintf('%s-%s-%s.%s', $prefix, $safeFilename, uniqid('', true), $file->guessExtension() ?: 'bin');

        try {
            $file->move($this->targetDirectory, $fileName);
        } catch (FileException) {
            throw new FileException('Impossible d\'enregistrer le fichier.');
        }

        return $fileName;
    }
}
