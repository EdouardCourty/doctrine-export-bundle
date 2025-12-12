<?php

declare(strict_types=1);

namespace Ecourty\DoctrineExportBundle\Tests\Fixtures\Processor;

use Ecourty\DoctrineExportBundle\Contract\EntityProcessorInterface;

/**
 * @internal
 */
class BracketProcessor implements EntityProcessorInterface
{
    public function process(object $entity, array $data, array $options): array
    {
        assert(is_string($data['firstName']));
        $data['firstName'] = '[' . $data['firstName'] . ']';

        return $data;
    }
}
