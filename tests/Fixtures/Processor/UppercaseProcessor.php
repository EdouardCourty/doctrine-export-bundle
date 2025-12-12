<?php

declare(strict_types=1);

namespace Ecourty\DoctrineExportBundle\Tests\Fixtures\Processor;

use Ecourty\DoctrineExportBundle\Contract\EntityProcessorInterface;

/**
 * @internal
 */
class UppercaseProcessor implements EntityProcessorInterface
{
    public function process(object $entity, array $data, array $options): array
    {
        assert(is_string($data['firstName']));
        $data['firstName'] = strtoupper($data['firstName']);

        return $data;
    }
}
