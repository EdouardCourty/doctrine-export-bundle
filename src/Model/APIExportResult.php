<?php

declare(strict_types=1);

namespace Ecourty\DoctrineExportBundle\Model;

use Ecourty\DoctrineExportBundle\Enum\ExportFormat;

/**
 * Value object holding the result of an API-based export.
 */
final readonly class APIExportResult
{
    /**
     * @param string       $url               Resource URL (e.g., Google Sheets spreadsheet URL)
     * @param int          $exportedCount     Number of entities exported
     * @param float        $durationInSeconds Export duration in seconds
     * @param ExportFormat $format            Export format used
     * @param class-string $entityClass       Entity class that was exported
     */
    public function __construct(
        public string $url,
        public int $exportedCount,
        public float $durationInSeconds,
        public ExportFormat $format,
        public string $entityClass,
    ) {
    }
}
