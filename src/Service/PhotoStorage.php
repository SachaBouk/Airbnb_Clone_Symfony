<?php

namespace App\Service;

use Symfony\Component\DependencyInjection\Attribute\Autowire;
use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\HttpFoundation\File\File;
use Symfony\Component\HttpFoundation\File\UploadedFile;

/**
 * Writes and deletes photo files on the local disk (public/uploads/listings by default).
 * Swapping this class is enough to store photos elsewhere (S3...).
 */
final readonly class PhotoStorage
{
    public function __construct(
        #[Autowire('%app.photo_directory%')]
        private string $directory,
        private Filesystem $filesystem,
    ) {
    }

    /**
     * Stores a file sent by a client.
     *
     * @return string the generated file name (random, so it can neither collide nor be guessed)
     */
    public function save(UploadedFile $file): string
    {
        $filename = $this->generateFilename($file);
        $file->move($this->directory, $filename);

        return $filename;
    }

    /**
     * Stores a copy of a local file (fixtures, imports); the original is left untouched.
     *
     * @return string the generated file name
     */
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

    /**
     * Deletes every stored photo file (used when the fixtures reset the database).
     */
    public function clear(): void
    {
        $this->filesystem->remove($this->directory);
        $this->filesystem->mkdir($this->directory);
    }

    private function generateFilename(File $file): string
    {
        // The extension comes from the real content (MIME type), never from the name sent by the client.
        return bin2hex(random_bytes(16)).'.'.($file->guessExtension() ?? 'jpg');
    }
}
