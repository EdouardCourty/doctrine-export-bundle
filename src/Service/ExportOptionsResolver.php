<?php

declare(strict_types=1);

namespace Ecourty\DoctrineExportBundle\Service;

use Ecourty\DoctrineExportBundle\Contract\DoctrineExporterInterface;

/**
 * @internal
 */
final class ExportOptionsResolver
{
    /**
     * @param array<string, mixed> $options
     */
    public function getDateTimeFormat(array $options): string
    {
        $format = $options[DoctrineExporterInterface::OPTION_DATETIME_FORMAT] ?? \DateTimeInterface::ATOM;

        return \is_string($format) ? $format : \DateTimeInterface::ATOM;
    }

    /**
     * @param array<string, mixed> $options
     */
    public function getNullValue(array $options): int|float|string|null
    {
        $nullValue = $options[DoctrineExporterInterface::OPTION_NULL_VALUE] ?? null;

        return \is_scalar($nullValue) && !\is_bool($nullValue) ? $nullValue : null;
    }

    /**
     * @param array<string, mixed> $options
     */
    public function shouldConvertBooleanToInteger(array $options): bool
    {
        return (bool) ($options[DoctrineExporterInterface::OPTION_BOOLEAN_TO_INTEGER] ?? true);
    }

    /**
     * @param array<string, mixed> $options
     */
    public function isDefaultProcessorDisabled(array $options): bool
    {
        return (bool) ($options[DoctrineExporterInterface::OPTION_DISABLE_DEFAULT_PROCESSOR] ?? false);
    }
}
