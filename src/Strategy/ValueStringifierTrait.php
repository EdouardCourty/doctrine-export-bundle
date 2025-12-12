<?php

declare(strict_types=1);

namespace Ecourty\DoctrineExportBundle\Strategy;

/**
 * @internal
 */
trait ValueStringifierTrait
{
    private function valueToString(mixed $value): string
    {
        if (is_array($value)) {
            return json_encode($value) ?: '[]';
        }

        return \is_scalar($value) || $value instanceof \Stringable ? (string) $value : '';
    }
}
