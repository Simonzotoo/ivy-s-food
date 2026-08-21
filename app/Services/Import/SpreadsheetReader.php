<?php

namespace App\Services\Import;

interface SpreadsheetReader
{
    /**
     * @return list<string>
     */
    public function headers(): array;

    /**
     * Iterate data rows as [header => trimmed string value], one row at a time.
     * Implementations must not load the whole file into memory.
     *
     * @return \Generator<int, array<string, string>>
     */
    public function rows(): \Generator;
}
