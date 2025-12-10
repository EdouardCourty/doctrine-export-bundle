<?php

declare(strict_types=1);

namespace Ecourty\DoctrineExportBundle\Tests\Strategy;

use Ecourty\DoctrineExportBundle\Enum\ExportFormat;
use Ecourty\DoctrineExportBundle\Strategy\JsonExportStrategy;
use PHPUnit\Framework\TestCase;

class JsonExportStrategyTest extends TestCase
{
    private JsonExportStrategy $strategy;

    protected function setUp(): void
    {
        $this->strategy = new JsonExportStrategy();
    }

    public function testGetFormat(): void
    {
        $this->assertSame(ExportFormat::JSON, $this->strategy->getFormat());
    }

    public function testGenerateHeader(): void
    {
        $header = $this->strategy->generateHeader(['id', 'name']);

        $this->assertNotNull($header);
        $this->assertStringContainsString('[', $header);
    }

    public function testFormatRow(): void
    {
        $this->strategy->generateHeader(['id', 'name']);

        $data = ['id' => 1, 'name' => 'John Doe'];
        $row = $this->strategy->formatRow($data);

        $this->assertJson($row);
        $decoded = json_decode($row, true);
        $this->assertIsArray($decoded);
        $this->assertSame(1, $decoded['id']);
        $this->assertSame('John Doe', $decoded['name']);
    }

    public function testFormatRowWithSpecialCharacters(): void
    {
        $this->strategy->generateHeader(['text']);

        $data = ['text' => 'Hello "World" & <tag>'];
        $row = $this->strategy->formatRow($data);

        $this->assertJson($row);
        $decoded = json_decode($row, true);
        $this->assertIsArray($decoded);
        $this->assertSame('Hello "World" & <tag>', $decoded['text']);
    }

    public function testGenerateFooter(): void
    {
        $footer = $this->strategy->generateFooter();

        $this->assertNotNull($footer);
        $this->assertStringContainsString(']', $footer);
    }

    public function testMultipleRowsWithCommas(): void
    {
        $this->strategy->generateHeader(['id']);

        $row1 = $this->strategy->formatRow(['id' => 1]);
        $this->assertStringNotContainsString(',', $row1);

        $row2 = $this->strategy->formatRow(['id' => 2]);
        $this->assertStringStartsWith(',', $row2);
    }
}
