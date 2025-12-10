<?php

declare(strict_types=1);

namespace Ecourty\DoctrineExportBundle\Tests\Service;

use Ecourty\DoctrineExportBundle\Enum\ExportFormat;
use Ecourty\DoctrineExportBundle\Exception\UnsupportedFormatException;
use Ecourty\DoctrineExportBundle\Service\ExportStrategyRegistry;
use Ecourty\DoctrineExportBundle\Strategy\CsvExportStrategy;
use Ecourty\DoctrineExportBundle\Strategy\JsonExportStrategy;
use Ecourty\DoctrineExportBundle\Strategy\XmlExportStrategy;
use PHPUnit\Framework\TestCase;

class ExportStrategyRegistryTest extends TestCase
{
    private ExportStrategyRegistry $registry;

    protected function setUp(): void
    {
        $strategies = [
            new CsvExportStrategy(),
            new JsonExportStrategy(),
            new XmlExportStrategy(),
        ];

        $this->registry = new ExportStrategyRegistry($strategies);
    }

    public function testGetStrategy(): void
    {
        $csvStrategy = $this->registry->getStrategy(ExportFormat::CSV);
        $this->assertInstanceOf(CsvExportStrategy::class, $csvStrategy);

        $jsonStrategy = $this->registry->getStrategy(ExportFormat::JSON);
        $this->assertInstanceOf(JsonExportStrategy::class, $jsonStrategy);

        $xmlStrategy = $this->registry->getStrategy(ExportFormat::XML);
        $this->assertInstanceOf(XmlExportStrategy::class, $xmlStrategy);
    }

    public function testGetStrategyThrowsExceptionForUnsupportedFormat(): void
    {
        $this->expectException(UnsupportedFormatException::class);
        $this->expectExceptionMessage('No export strategy found for format');

        // Create an empty registry
        $emptyRegistry = new ExportStrategyRegistry([]);
        $emptyRegistry->getStrategy(ExportFormat::CSV);
    }

    public function testHasStrategy(): void
    {
        $this->assertTrue($this->registry->hasStrategy(ExportFormat::CSV));
        $this->assertTrue($this->registry->hasStrategy(ExportFormat::JSON));
        $this->assertTrue($this->registry->hasStrategy(ExportFormat::XML));
    }

    public function testGetSupportedFormats(): void
    {
        $formats = $this->registry->getSupportedFormats();

        $this->assertCount(3, $formats);
        $this->assertContains(ExportFormat::CSV, $formats);
        $this->assertContains(ExportFormat::JSON, $formats);
        $this->assertContains(ExportFormat::XML, $formats);
    }
}
