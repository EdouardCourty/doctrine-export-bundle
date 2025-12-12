<?php

declare(strict_types=1);

namespace Ecourty\DoctrineExportBundle\Tests\Fixtures\Processor;

use Ecourty\DoctrineExportBundle\Contract\EntityProcessorInterface;
use Ecourty\DoctrineExportBundle\Tests\Fixtures\Entity\User;

/**
 * Processor that handles all fields without relying on the default processor.
 *
 * @internal
 */
class FullyCustomProcessor implements EntityProcessorInterface
{
    public function process(object $entity, array $data, array $options): array
    {
        assert($entity instanceof User);

        foreach (array_keys($data) as $field) {
            $data[$field] = match ($field) {
                'firstName' => 'CUSTOM_' . $entity->getFirstName(),
                'lastName' => 'CUSTOM_' . $entity->getLastName(),
                'email' => 'CUSTOM_' . $entity->getEmail(),
                'age' => $entity->getAge() * 10,
                default => 'CUSTOM_VALUE',
            };
        }

        return $data;
    }
}
