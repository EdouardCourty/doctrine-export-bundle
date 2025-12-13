<?php

declare(strict_types=1);

namespace Ecourty\DoctrineExportBundle\Event;

use Ecourty\DoctrineExportBundle\Enum\ExportFormat;

class PreExportEvent
{
    /**
     * @param class-string          $entityClass
     * @param array<string, mixed>  $criteria
     * @param array<string, string> $orderBy
     * @param array<int, string>    $fields
     * @param array<string, mixed>  $options
     */
    public function __construct(
        private readonly string $entityClass,
        private readonly ExportFormat $format,
        private readonly array $criteria,
        private readonly ?int $limit,
        private readonly ?int $offset,
        private readonly array $orderBy,
        private readonly array $fields,
        private readonly array $options,
    ) {
    }

    /**
     * @return class-string
     */
    public function getEntityClass(): string
    {
        return $this->entityClass;
    }

    public function getFormat(): ExportFormat
    {
        return $this->format;
    }

    /**
     * @return array<string, mixed>
     */
    public function getCriteria(): array
    {
        return $this->criteria;
    }

    public function getLimit(): ?int
    {
        return $this->limit;
    }

    public function getOffset(): ?int
    {
        return $this->offset;
    }

    /**
     * @return array<string, string>
     */
    public function getOrderBy(): array
    {
        return $this->orderBy;
    }

    /**
     * @return array<int, string>
     */
    public function getFields(): array
    {
        return $this->fields;
    }

    /**
     * @return array<string, mixed>
     */
    public function getOptions(): array
    {
        return $this->options;
    }
}
