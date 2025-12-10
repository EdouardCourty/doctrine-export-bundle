<?php

declare(strict_types=1);

namespace Ecourty\DoctrineExportBundle\Strategy;

use Ecourty\DoctrineExportBundle\Contract\ExportStrategyInterface;
use Ecourty\DoctrineExportBundle\Enum\ExportFormat;

class XmlExportStrategy implements ExportStrategyInterface
{
    private string $rootElement;
    private string $itemElement;
    private bool $prettyPrint;
    private ?\XMLWriter $writer = null;

    public function __construct(
        string $rootElement = 'data',
        string $itemElement = 'item',
        bool $prettyPrint = false,
    ) {
        $this->rootElement = $rootElement;
        $this->itemElement = $itemElement;
        $this->prettyPrint = $prettyPrint;
    }

    public function getFormat(): ExportFormat
    {
        return ExportFormat::XML;
    }

    public function generateHeader(array $fields): ?string
    {
        $this->writer = null; // Reset writer for new export

        return '<?xml version="1.0" encoding="UTF-8"?>' . \PHP_EOL . '<' . $this->rootElement . '>' . \PHP_EOL;
    }

    public function formatRow(array $data): string
    {
        if (null === $this->writer) {
            $this->writer = new \XMLWriter();
            $this->writer->openMemory();
            $this->writer->setIndent($this->prettyPrint);
            if ($this->prettyPrint) {
                $this->writer->setIndentString('    ');
            }
        }

        $this->writer->startElement($this->itemElement);

        foreach ($data as $key => $value) {
            $safeKey = $this->sanitizeTagName((string) $key);
            $stringValue = $this->valueToString($value);

            $this->writer->writeElement($safeKey, $stringValue);
        }

        $this->writer->endElement();

        $result = $this->writer->flush();

        // Add proper formatting
        if ($this->prettyPrint) {
            return '  ' . trim((string) $result) . \PHP_EOL;
        }

        return $result . \PHP_EOL;
    }

    private function valueToString(mixed $value): string
    {
        if (is_array($value)) {
            return json_encode($value) ?: '[]';
        }

        return \is_scalar($value) || $value instanceof \Stringable ? (string) $value : '';
    }

    public function generateFooter(): ?string
    {
        // Clean up writer
        $this->writer = null;

        return '</' . $this->rootElement . '>' . \PHP_EOL;
    }

    private function sanitizeTagName(string $name): string
    {
        // Remove invalid XML tag characters
        $sanitized = preg_replace('/[^a-zA-Z0-9_\-.]/', '_', $name);

        if (null === $sanitized) {
            return 'field';
        }

        // Ensure it doesn't start with a number, dot, or hyphen
        if (preg_match('/^[0-9.\-]/', $sanitized)) {
            $sanitized = '_' . $sanitized;
        }

        // Ensure it's not empty
        if ('' === $sanitized) {
            return 'field';
        }

        return $sanitized;
    }
}
