<?php

declare(strict_types=1);

namespace Ecourty\DoctrineExportBundle\Strategy;

use Ecourty\DoctrineExportBundle\Contract\ExportStrategyInterface;
use Ecourty\DoctrineExportBundle\Enum\ExportFormat;

class JsonExportStrategy implements ExportStrategyInterface
{
    private bool $prettyPrint;
    private int $options;
    private bool $isFirstRow = true;

    public function __construct(
        bool $prettyPrint = false,
        int $options = \JSON_UNESCAPED_UNICODE | \JSON_UNESCAPED_SLASHES,
    ) {
        $this->prettyPrint = $prettyPrint;
        $this->options = $options;
    }

    public function getFormat(): ExportFormat
    {
        return ExportFormat::JSON;
    }

    public function generateHeader(array $fields): ?string
    {
        $this->isFirstRow = true;

        return '[';
    }

    public function formatRow(array $data): string
    {
        $options = $this->options;
        if ($this->prettyPrint) {
            $options |= \JSON_PRETTY_PRINT;
        }

        $json = json_encode($data, $options);

        if ($this->isFirstRow) {
            $this->isFirstRow = false;

            return \PHP_EOL . $json;
        }

        return ',' . \PHP_EOL . $json;
    }

    public function generateFooter(): ?string
    {
        return \PHP_EOL . ']' . \PHP_EOL;
    }
}
