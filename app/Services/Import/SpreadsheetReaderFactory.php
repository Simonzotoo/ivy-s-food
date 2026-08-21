<?php

namespace App\Services\Import;

use RuntimeException;

class SpreadsheetReaderFactory
{
    public static function make(string $absolutePath, string $extension): SpreadsheetReader
    {
        return match (strtolower($extension)) {
            'csv' => new CsvSpreadsheetReader($absolutePath),
            'xlsx' => new XlsxSpreadsheetReader($absolutePath),
            default => throw new RuntimeException('Unsupported import file type.'),
        };
    }
}
