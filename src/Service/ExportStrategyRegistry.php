<?php

declare(strict_types=1);

namespace Ecourty\DoctrineExportBundle\Service;

use Ecourty\DoctrineExportBundle\Contract\ExportStrategyInterface;
use Ecourty\DoctrineExportBundle\Enum\ExportFormat;
use Ecourty\DoctrineExportBundle\Exception\UnsupportedFormatException;

class ExportStrategyRegistry
{
    /** @var array<string, ExportStrategyInterface> */
    private array $strategies = [];

    /**
     * @param iterable<ExportStrategyInterface> $strategies
     */
    public function __construct(iterable $strategies)
    {
        foreach ($strategies as $strategy) {
            $this->strategies[$strategy->getFormat()->value] = $strategy;
        }
    }

    public function getStrategy(ExportFormat $format): ExportStrategyInterface
    {
        if (!isset($this->strategies[$format->value])) {
            throw new UnsupportedFormatException(
                \sprintf('No export strategy found for format "%s"', $format->value)
            );
        }

        return $this->strategies[$format->value];
    }

    public function hasStrategy(ExportFormat $format): bool
    {
        return isset($this->strategies[$format->value]);
    }

    /**
     * @return ExportFormat[]
     */
    public function getSupportedFormats(): array
    {
        return array_map(
            fn (string $value) => ExportFormat::from($value),
            array_keys($this->strategies)
        );
    }
}
