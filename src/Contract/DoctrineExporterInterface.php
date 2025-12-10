<?php

declare(strict_types=1);

namespace Ecourty\DoctrineExportBundle\Contract;

use Ecourty\DoctrineExportBundle\Enum\ExportFormat;

interface DoctrineExporterInterface
{
    public const OPTION_BOOLEAN_TO_INTEGER = 'boolean_to_integer';
    public const OPTION_DATETIME_FORMAT = 'datetime_format';
    public const OPTION_NULL_VALUE = 'null_value';

    public const BOOLEAN_TRUE_AS_ONE = 1;
    public const BOOLEAN_FALSE_AS_ZERO = 0;
    public const BOOLEAN_TRUE_AS_STRING = 'true';
    public const BOOLEAN_FALSE_AS_STRING = 'false';

    /**
     * Export data to a file.
     *
     * @param class-string          $entityClass Entity class (e.g., User::class)
     * @param array<string, mixed>  $criteria    Search criteria (findBy compatible)
     * @param array<string, string> $orderBy     Sort order
     * @param array<int, string>    $fields      Fields to export (empty = all fields)
     * @param array<string, mixed>  $options     Export options (OPTION_* constants)
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
    ): void;

    /**
     * Returns a generator to stream data.
     *
     * @param class-string          $entityClass Entity class
     * @param array<string, mixed>  $criteria    Search criteria
     * @param array<string, string> $orderBy     Sort order
     * @param array<int, string>    $fields      Fields to export (empty = all fields)
     * @param array<string, mixed>  $options     Export options (OPTION_* constants)
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
    ): \Generator;
}
