<?php

declare(strict_types=1);

namespace Ecourty\DoctrineExportBundle\Strategy;

use Ecourty\DoctrineExportBundle\Contract\ExportStrategyInterface;
use Ecourty\DoctrineExportBundle\Enum\ExportFormat;

class CsvExportStrategy implements ExportStrategyInterface
{
    private string $delimiter;
    private string $enclosure;

    public function __construct(
        string $delimiter = ',',
        string $enclosure = '"',
    ) {
        $this->delimiter = $delimiter;
        $this->enclosure = $enclosure;
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
        $values = array_map(
            fn (mixed $value): string => $this->valueToString($value),
            array_values($data)
        );

        $formatted = [];
        foreach ($values as $value) {
            $formatted[] = $this->escapeField($value);
        }

        return implode($this->delimiter, $formatted) . \PHP_EOL;
    }

    private function valueToString(mixed $value): string
    {
        if (is_array($value)) {
            return json_encode($value) ?: '[]';
        }

        return \is_scalar($value) || $value instanceof \Stringable ? (string) $value : '';
    }

    private function escapeField(string $value): string
    {
        // Check if field needs quoting
        $needsQuoting = str_contains($value, $this->delimiter)
            || str_contains($value, $this->enclosure)
            || str_contains($value, "\n")
            || str_contains($value, "\r");

        if (!$needsQuoting) {
            return $value;
        }

        // Escape enclosure characters by doubling them
        $escaped = str_replace($this->enclosure, $this->enclosure . $this->enclosure, $value);

        // Wrap in enclosure
        return $this->enclosure . $escaped . $this->enclosure;
    }

    public function generateFooter(): ?string
    {
        return null;
    }
}
