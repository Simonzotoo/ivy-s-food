<?php

namespace App\Services\Import;

use RuntimeException;
use XMLReader;
use ZipArchive;

/**
 * Minimal, dependency-free XLSX reader.
 *
 * Reads only cached cell values (the <v> element) from the first worksheet and the
 * shared-string table. Formula definitions (<f>), macros, external references and any
 * other workbook part are never opened or evaluated, so an uploaded file cannot execute
 * anything during import - only its first sheet's literal values are read.
 */
class XlsxSpreadsheetReader implements SpreadsheetReader
{
    private const MAX_SHARED_STRINGS = 100000;

    /** @var list<string> */
    private array $sharedStrings = [];

    private string $sheetEntry;

    /** @var list<string> */
    private array $headers;

    public function __construct(private readonly string $absolutePath)
    {
        $this->assertValidWorkbook();
        $this->sharedStrings = $this->loadSharedStrings();
        $this->sheetEntry = $this->resolveFirstSheetEntry();
        $this->headers = $this->readRowAt(1) ?? [];
    }

    public function headers(): array
    {
        return $this->headers;
    }

    public function rows(): \Generator
    {
        $reader = $this->openSheetReader();

        try {
            $rowNumber = 0;
            while ($reader->read()) {
                if ($reader->nodeType !== XMLReader::ELEMENT || $reader->localName !== 'row') {
                    continue;
                }

                $rowNumber++;
                if ($rowNumber === 1) {
                    continue; // header row
                }

                $cells = $this->parseRowXml($reader->readOuterXml());
                if ($cells === []) {
                    continue;
                }

                yield $this->combine($cells);
            }
        } finally {
            $reader->close();
        }
    }

    private function assertValidWorkbook(): void
    {
        $zip = new ZipArchive;
        if ($zip->open($this->absolutePath) !== true) {
            throw new RuntimeException('The uploaded file is not a valid Excel workbook.');
        }

        $contentTypes = $zip->getFromName('[Content_Types].xml');
        $zip->close();

        if ($contentTypes === false || ! str_contains($contentTypes, 'spreadsheetml')) {
            throw new RuntimeException('The uploaded file is not a valid Excel workbook.');
        }
    }

    /**
     * @return list<string>
     */
    private function loadSharedStrings(): array
    {
        $zip = new ZipArchive;
        $hasSharedStrings = $zip->open($this->absolutePath) === true && $zip->locateName('xl/sharedStrings.xml') !== false;
        $zip->close();

        if (! $hasSharedStrings) {
            return [];
        }

        $uri = 'zip://'.$this->absolutePath.'#xl/sharedStrings.xml';
        $reader = new XMLReader;
        if (! $reader->open($uri)) {
            return [];
        }
        $reader->setParserProperty(XMLReader::SUBST_ENTITIES, false);

        $strings = [];
        while ($reader->read()) {
            if ($reader->nodeType === XMLReader::ELEMENT && $reader->localName === 'si') {
                $node = simplexml_load_string($reader->readOuterXml());
                $strings[] = $node === false ? '' : implode('', $node->xpath('.//*[local-name()="t"]/text()') ?: []);
                if (count($strings) > self::MAX_SHARED_STRINGS) {
                    throw new RuntimeException('This workbook has too many unique text values to import safely.');
                }
            }
        }
        $reader->close();

        return $strings;
    }

    private function resolveFirstSheetEntry(): string
    {
        $zip = new ZipArchive;
        if ($zip->open($this->absolutePath) !== true) {
            throw new RuntimeException('The uploaded file is not a valid Excel workbook.');
        }

        $entries = [];
        for ($i = 0; $i < $zip->numFiles; $i++) {
            $name = $zip->getNameIndex($i);
            if ($name !== false && preg_match('#^xl/worksheets/sheet(\d+)\.xml$#', $name, $m)) {
                $entries[(int) $m[1]] = $name;
            }
        }
        $zip->close();

        if ($entries === []) {
            throw new RuntimeException('This workbook has no readable worksheet.');
        }

        ksort($entries);

        return reset($entries);
    }

    private function openSheetReader(): XMLReader
    {
        $reader = new XMLReader;
        $uri = 'zip://'.$this->absolutePath.'#'.$this->sheetEntry;
        if (! $reader->open($uri)) {
            throw new RuntimeException('Unable to read the worksheet.');
        }
        $reader->setParserProperty(XMLReader::SUBST_ENTITIES, false);

        return $reader;
    }

    /**
     * @return array<int, string>|null column index (0-based) => value
     */
    private function readRowAt(int $targetRow): ?array
    {
        $reader = $this->openSheetReader();
        try {
            $rowNumber = 0;
            while ($reader->read()) {
                if ($reader->nodeType !== XMLReader::ELEMENT || $reader->localName !== 'row') {
                    continue;
                }
                $rowNumber++;
                if ($rowNumber !== $targetRow) {
                    continue;
                }

                return $this->parseRowXml($reader->readOuterXml());
            }
        } finally {
            $reader->close();
        }

        return null;
    }

    /**
     * @return array<int, string> column index (0-based) => value
     */
    private function parseRowXml(string $xml): array
    {
        $node = simplexml_load_string($xml);
        if ($node === false) {
            return [];
        }

        $cells = [];
        foreach ($node->xpath('./*[local-name()="c"]') ?: [] as $cell) {
            $ref = (string) $cell['r'];
            $col = $ref !== '' ? $this->columnIndex($ref) : count($cells);
            $type = (string) $cell['t'];
            $valueNode = $cell->xpath('./*[local-name()="v"]');
            $inlineNode = $cell->xpath('./*[local-name()="is"]//*[local-name()="t"]');

            $value = match (true) {
                $type === 's' && $valueNode !== [] => $this->sharedStrings[(int) (string) $valueNode[0]] ?? '',
                $type === 'inlineStr' => implode('', array_map(fn ($t) => (string) $t, $inlineNode)),
                $valueNode !== [] => (string) $valueNode[0],
                default => '',
            };

            $cells[$col] = $value;
        }

        return $cells;
    }

    private function columnIndex(string $cellRef): int
    {
        preg_match('/^([A-Z]+)/', $cellRef, $m);
        $letters = $m[1] ?? 'A';
        $index = 0;
        foreach (str_split($letters) as $char) {
            $index = $index * 26 + (ord($char) - ord('A') + 1);
        }

        return $index - 1;
    }

    /**
     * @param  array<int, string>  $cells
     * @return array<string, string>
     */
    private function combine(array $cells): array
    {
        $row = [];
        foreach ($this->headers as $col => $header) {
            $row[$header] = trim((string) ($cells[$col] ?? ''));
        }

        return $row;
    }
}
