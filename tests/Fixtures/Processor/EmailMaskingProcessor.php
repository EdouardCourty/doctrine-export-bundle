<?php

declare(strict_types=1);

namespace Ecourty\DoctrineExportBundle\Tests\Fixtures\Processor;

use Ecourty\DoctrineExportBundle\Contract\EntityProcessorInterface;

/**
 * @internal
 */
class EmailMaskingProcessor implements EntityProcessorInterface
{
    public function process(object $entity, array $data, array $options): array
    {
        assert(is_string($data['email']) || null === $data['email']);
        $data['email'] = $this->maskEmail($data['email']);

        return $data;
    }

    private function maskEmail(?string $email): ?string
    {
        if (null === $email) {
            return null;
        }

        return preg_replace('/(?<=.).(?=.*@)/', '*', $email);
    }
}
