<?php

declare(strict_types=1);

namespace Ecourty\DoctrineExportBundle\Tests\Strategy;

use Ecourty\DoctrineExportBundle\Enum\ExportFormat;
use Ecourty\DoctrineExportBundle\Strategy\CsvExportStrategy;
use PHPUnit\Framework\TestCase;

class CsvExportStrategyTest extends TestCase
{
    private CsvExportStrategy $strategy;

    protected function setUp(): void
    {
        $this->strategy = new CsvExportStrategy();
    }

    public function testGetFormat(): void
    {
        $this->assertSame(ExportFormat::CSV, $this->strategy->getFormat());
    }

    public function testGenerateHeader(): void
    {
        $fields = ['id', 'name', 'email'];
        $header = $this->strategy->generateHeader($fields);

        $this->assertNotNull($header);
        $this->assertStringContainsString('id', $header);
        $this->assertStringContainsString('name', $header);
        $this->assertStringContainsString('email', $header);
    }

    public function testFormatRow(): void
    {
        $data = ['id' => 1, 'name' => 'John Doe', 'email' => 'john@example.com'];
        $row = $this->strategy->formatRow($data);

        $this->assertStringContainsString('1', $row);
        $this->assertStringContainsString('John Doe', $row);
        $this->assertStringContainsString('john@example.com', $row);
    }

    public function testFormatRowWithSpecialCharacters(): void
    {
        $data = ['text' => 'Hello, "World"'];
        $row = $this->strategy->formatRow($data);

        $this->assertStringContainsString('Hello', $row);
    }

    public function testGenerateFooter(): void
    {
        $this->assertNull($this->strategy->generateFooter());
    }
}
