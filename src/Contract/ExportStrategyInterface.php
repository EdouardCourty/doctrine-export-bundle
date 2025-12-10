<?php

declare(strict_types=1);

namespace Ecourty\DoctrineExportBundle\Contract;

use Ecourty\DoctrineExportBundle\Enum\ExportFormat;

interface ExportStrategyInterface
{
    public function getFormat(): ExportFormat;

    /**
     * @param array<int, string> $fields
     */
    public function generateHeader(array $fields): ?string;

    /**
     * @param array<string, mixed> $data
     */
    public function formatRow(array $data): string;

    public function generateFooter(): ?string;
}
