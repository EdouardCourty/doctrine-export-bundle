<?php

declare(strict_types=1);

namespace Ecourty\DoctrineExportBundle\Contract;

use Ecourty\DoctrineExportBundle\Enum\ExportFormat;

interface DoctrineExporterInterface
{
    /**
     * Convert boolean values to integers (1/0) instead of strings ('true'/'false').
     * Value: bool - Set to true to enable conversion.
     */
    public const OPTION_BOOLEAN_TO_INTEGER = 'boolean_to_integer';

    /**
     * Custom format for DateTime/DateTimeImmutable fields.
     * Value: string - PHP date format (e.g., 'Y-m-d H:i:s', 'c' for ISO8601).
     */
    public const OPTION_DATETIME_FORMAT = 'datetime_format';

    /**
     * Custom string representation for null values.
     * Value: string - Any string to represent null (e.g., 'NULL', 'N/A', '').
     */
    public const OPTION_NULL_VALUE = 'null_value';

    /**
     * Throw exception if a requested field doesn't exist on the entity.
     * Value: bool - Set to true to enable strict validation (default: false).
     */
    public const OPTION_STRICT_FIELDS = 'strict_fields';

    /**
     * Disable the default entity processor for performance optimization.
     * Value: bool - Set to true when using custom processors that handle all processing.
     */
    public const OPTION_DISABLE_DEFAULT_PROCESSOR = 'disable_default_processor';

    public const BOOLEAN_TRUE_AS_ONE = 1;
    public const BOOLEAN_FALSE_AS_ZERO = 0;
    public const BOOLEAN_TRUE_AS_STRING = 'true';
    public const BOOLEAN_FALSE_AS_STRING = 'false';

    /**
     * Export data to a file.
     *
     * @param class-string                         $entityClass Entity class (e.g., User::class)
     * @param array<string, mixed>                 $criteria    Search criteria (findBy compatible)
     * @param array<string, string>                $orderBy     Sort order
     * @param array<int, string>                   $fields      Fields to export (empty = all fields)
     * @param array<string, mixed>                 $options     Export options (OPTION_* constants)
     * @param array<int, EntityProcessorInterface> $processors  Custom entity processors
     */
    public function exportToFile(
        string $entityClass,
        ExportFormat $format,
        string $filePath,
        array $criteria = [],
        ?int $limit = null,
        ?int $offset = null,
        array $orderBy = [],
        array $fields = [],
        array $options = [],
        array $processors = [],
    ): void;

    /**
     * Returns a generator to stream data.
     *
     * @param class-string                         $entityClass Entity class
     * @param array<string, mixed>                 $criteria    Search criteria
     * @param array<string, string>                $orderBy     Sort order
     * @param array<int, string>                   $fields      Fields to export (empty = all fields)
     * @param array<string, mixed>                 $options     Export options (OPTION_* constants)
     * @param array<int, EntityProcessorInterface> $processors  Custom entity processors
     *
     * @return \Generator<string> Generator that yields export lines
     */
    public function exportToGenerator(
        string $entityClass,
        ExportFormat $format,
        array $criteria = [],
        ?int $limit = null,
        ?int $offset = null,
        array $orderBy = [],
        array $fields = [],
        array $options = [],
        array $processors = [],
    ): \Generator;
}
