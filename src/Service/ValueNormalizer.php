<?php

declare(strict_types=1);

namespace Ecourty\DoctrineExportBundle\Service;

use Ecourty\DoctrineExportBundle\Contract\DoctrineExporterInterface;

/**
 * @internal
 */
final class ValueNormalizer
{
    public function __construct(
        private readonly ExportOptionsResolver $optionsResolver,
    ) {
    }

    /**
     * @param array<string, mixed> $options
     */
    public function normalize(mixed $value, array $options): int|float|string|null
    {
        if ($value instanceof \DateTimeInterface) {
            return $value->format($this->optionsResolver->getDateTimeFormat($options));
        }

        if (\is_bool($value)) {
            if ($this->optionsResolver->shouldConvertBooleanToInteger($options)) {
                return $value
                    ? DoctrineExporterInterface::BOOLEAN_TRUE_AS_ONE
                    : DoctrineExporterInterface::BOOLEAN_FALSE_AS_ZERO;
            }

            return $value
                ? DoctrineExporterInterface::BOOLEAN_TRUE_AS_STRING
                : DoctrineExporterInterface::BOOLEAN_FALSE_AS_STRING;
        }

        if (null === $value) {
            return $this->optionsResolver->getNullValue($options);
        }

        if (\is_array($value)) {
            return json_encode($value) ?: '[]';
        }

        if (\is_object($value)) {
            if (method_exists($value, '__toString')) {
                return (string) $value;
            }

            return $value::class;
        }

        if (\is_scalar($value)) {
            return $value;
        }

        return null;
    }
}
