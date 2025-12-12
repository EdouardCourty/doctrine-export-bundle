<?php

declare(strict_types=1);

namespace Ecourty\DoctrineExportBundle\Service;

use Ecourty\DoctrineExportBundle\Contract\EntityProcessorInterface;

/**
 * Orchestrates the chain of entity processors.
 *
 * @internal
 */
final class EntityProcessorChain
{
    public function __construct(
        private readonly DefaultEntityProcessor $defaultProcessor,
        private readonly ExportOptionsResolver $optionsResolver,
    ) {
    }

    /**
     * Process entity data through the processor chain.
     *
     * @param array<int, string>                   $fields           Fields to extract
     * @param array<string, mixed>                 $options          Export options
     * @param array<int, EntityProcessorInterface> $customProcessors Custom processors
     *
     * @return array<string, mixed>
     */
    public function process(
        object $entity,
        array $fields,
        array $options,
        array $customProcessors = [],
    ): array {
        $data = [];
        foreach ($fields as $field) {
            $data[$field] = null;
        }

        $disableDefault = $this->optionsResolver->isDefaultProcessorDisabled($options);
        $processors = $disableDefault ? $customProcessors : [$this->defaultProcessor, ...$customProcessors];

        foreach ($processors as $processor) {
            $data = $processor->process($entity, $data, $options);
        }

        return $data;
    }
}
