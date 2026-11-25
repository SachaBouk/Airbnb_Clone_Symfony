<?php

namespace App\Service;

use Symfony\Component\DependencyInjection\Attribute\Autowire;
use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\HttpFoundation\File\File;
use Symfony\Component\HttpFoundation\File\UploadedFile;

final readonly class PhotoStorage
{
    public function __construct(
        #[Autowire('%app.photo_directory%')]
        private string $directory,
        private Filesystem $filesystem,
    ) {
    }

    public function save(UploadedFile $file): string
    {
        $filename = $this->generateFilename($file);
        $file->move($this->directory, $filename);

        return $filename;
    }

    public function copy(string $sourcePath): string
    {
        $filename = $this->generateFilename(new File($sourcePath));
        $this->filesystem->copy($sourcePath, $this->directory.'/'.$filename);

        return $filename;
    }

    public function remove(string $filename): void
    {
        $this->filesystem->remove($this->directory.'/'.basename($filename));
    }

    public function clear(): void
    {
        $this->filesystem->remove($this->directory);
        $this->filesystem->mkdir($this->directory);
    }

    private function generateFilename(File $file): string
    {
        return bin2hex(random_bytes(16)).'.'.($file->guessExtension() ?? 'jpg');
    }
}
