<?php

namespace App\Services\Import;

use RuntimeException;

class CsvSpreadsheetReader implements SpreadsheetReader
{
    /** @var list<string> */
    private array $headers;

    public function __construct(private readonly string $absolutePath)
    {
        $this->headers = $this->readHeaders();
    }

    public function headers(): array
    {
        return $this->headers;
    }

    public function rows(): \Generator
    {
        $handle = fopen($this->absolutePath, 'rb');
        if ($handle === false) {
            throw new RuntimeException('Unable to open import file.');
        }

        try {
            $this->skipBom($handle);
            fgetcsv($handle, escape: ''); // skip header row, already parsed
            while (($values = fgetcsv($handle, escape: '')) !== false) {
                if ($values === [null] || $values === false) {
                    continue;
                }

                yield $this->combine($values);
            }
        } finally {
            fclose($handle);
        }
    }

    /**
     * @return list<string>
     */
    private function readHeaders(): array
    {
        $handle = fopen($this->absolutePath, 'rb');
        if ($handle === false) {
            throw new RuntimeException('Unable to open import file.');
        }

        try {
            $this->skipBom($handle);
            $values = fgetcsv($handle, escape: '');
            if ($values === false) {
                return [];
            }

            return array_map(static fn ($value): string => trim((string) $value), $values);
        } finally {
            fclose($handle);
        }
    }

    /**
     * @param  array<int, string|null>  $values
     * @return array<string, string>
     */
    private function combine(array $values): array
    {
        $row = [];
        foreach ($this->headers as $i => $header) {
            $row[$header] = trim((string) ($values[$i] ?? ''));
        }

        return $row;
    }

    /**
     * @param  resource  $handle
     */
    private function skipBom($handle): void
    {
        $bom = fread($handle, 3);
        if ($bom !== "\xEF\xBB\xBF") {
            rewind($handle);
        }
    }
}
