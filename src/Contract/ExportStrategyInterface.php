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

    /**
     * Optional lifecycle method called before export begins.
     * Allows strategies to initialize resources (e.g., create spreadsheet).
     *
     * @param array<int, string>   $fields  Field names to export
     * @param array<string, mixed> $options Export options
     */
    public function prepare(array $fields, array $options): void;

    /**
     * Optional lifecycle method called after export completes.
     * Allows strategies to flush buffers and finalize resources.
     */
    public function finalize(): void;

    /**
     * Returns the export URL for API-based strategies.
     * File-based strategies (CSV, JSON, XML) return null.
     *
     * @return string|null Export URL (e.g., Google Sheets URL) or null
     */
    public function getExportUrl(): ?string;

    /**
     * Indicates if this strategy supports file-based export.
     * Return false for API-based strategies (e.g., Google Sheets).
     *
     * @return bool True if supports file export, false for API-based strategies
     */
    public function supportsFileExport(): bool;
}
