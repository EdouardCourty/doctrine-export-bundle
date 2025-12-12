<?php

declare(strict_types=1);

namespace Ecourty\DoctrineExportBundle\Contract;

/**
 * Custom entity processor for export data transformation.
 *
 * Processors are executed in a chain in the order they are provided.
 * Each processor receives data already extracted by previous processors and can modify/enrich it.
 *
 * DefaultEntityProcessor always executes first to extract raw entity data.
 * Custom processors execute in the order given in the $processors parameter.
 */
interface EntityProcessorInterface
{
    /**
     * Process entity data (modify/enrich).
     *
     * @param array<string, mixed> $data    Data already extracted by previous processors
     * @param array<string, mixed> $options Export options (OPTION_* constants)
     *
     * @return array<string, mixed> Transformed/enriched data
     */
    public function process(object $entity, array $data, array $options): array;
}
