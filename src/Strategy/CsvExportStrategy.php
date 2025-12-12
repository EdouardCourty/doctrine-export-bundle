<?php

declare(strict_types=1);

namespace Ecourty\DoctrineExportBundle\Strategy;

use Ecourty\DoctrineExportBundle\Contract\ExportStrategyInterface;
use Ecourty\DoctrineExportBundle\Enum\ExportFormat;

class CsvExportStrategy implements ExportStrategyInterface
{
    use ValueStringifierTrait;
    private string $delimiter;
    private string $enclosure;
    private string $escape;

    public function __construct(
        string $delimiter = ',',
        string $enclosure = '"',
        string $escape = '\\',
    ) {
        $this->delimiter = $delimiter;
        $this->enclosure = $enclosure;
        $this->escape = $escape;
    }

    public function getFormat(): ExportFormat
    {
        return ExportFormat::CSV;
    }

    /**
     * @param array<int, string> $fields
     */
    public function generateHeader(array $fields): ?string
    {
        return $this->formatRow(array_combine($fields, $fields) ?: []);
    }

    public function formatRow(array $data): string
    {
        $stream = fopen('php://memory', 'r+');
        if (false === $stream) {
            throw new \RuntimeException('Failed to open temporary stream for CSV formatting');
        }

        $values = [];
        foreach ($data as $value) {
            $values[] = $this->valueToString($value);
        }

        fputcsv($stream, $values, $this->delimiter, $this->enclosure, $this->escape);
        rewind($stream);
        $result = stream_get_contents($stream);
        fclose($stream);

        unset($values);

        return false !== $result ? $result : '';
    }

    public function generateFooter(): ?string
    {
        return null;
    }
}
