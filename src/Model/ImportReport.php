<?php

namespace App\Model;

final class ImportReport
{
    public int $created = 0;

    public int $skipped = 0;

    public int $createdHosts = 0;

    public int $createdAmenities = 0;

    public array $errors = [];

    public function addError(int $lineNumber, string $message): void
    {
        $this->errors[$lineNumber] = $message;
    }
}
