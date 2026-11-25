<?php

namespace App\Model;

/**
 * What an import did, line by line.
 */
final class ImportReport
{
    public int $created = 0;

    /** Lines describing a listing that already exists (same title, city and host). */
    public int $skipped = 0;

    public int $createdHosts = 0;

    public int $createdAmenities = 0;

    /** @var array<int, string> error message indexed by line number (1-based) */
    public array $errors = [];

    public function addError(int $lineNumber, string $message): void
    {
        $this->errors[$lineNumber] = $message;
    }
}
