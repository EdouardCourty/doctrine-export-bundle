<?php

declare(strict_types=1);

namespace Ecourty\DoctrineExportBundle\Tests\Fixtures\Processor;

use Ecourty\DoctrineExportBundle\Contract\EntityProcessorInterface;
use Ecourty\DoctrineExportBundle\Tests\Fixtures\Entity\User;

/**
 * @internal
 */
class UserVirtualFieldsProcessor implements EntityProcessorInterface
{
    public function process(object $entity, array $data, array $options): array
    {
        assert($entity instanceof User);
        $data['displayName'] = strtoupper($entity->getFirstName() . ' ' . $entity->getLastName());
        $data['ageCategory'] = $entity->getAge() >= 30 ? 'senior' : 'junior';

        return $data;
    }
}
