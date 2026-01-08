<?php

declare(strict_types=1);

namespace Ecourty\DoctrineExportBundle\Exception;

/**
 * Exception thrown when an operation is not supported for a specific strategy.
 */
class UnsupportedOperationException extends ExportException
{
    public static function fileExportNotSupported(string $format): self
    {
        return new self(
            sprintf(
                'Format "%s" does not support file export.',
                $format
            )
        );
    }
}
