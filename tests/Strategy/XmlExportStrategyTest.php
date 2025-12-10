<?php

declare(strict_types=1);

namespace Ecourty\DoctrineExportBundle\Tests\Strategy;

use Ecourty\DoctrineExportBundle\Enum\ExportFormat;
use Ecourty\DoctrineExportBundle\Strategy\XmlExportStrategy;
use PHPUnit\Framework\TestCase;

class XmlExportStrategyTest extends TestCase
{
    private XmlExportStrategy $strategy;

    protected function setUp(): void
    {
        $this->strategy = new XmlExportStrategy();
    }

    public function testGetFormat(): void
    {
        $this->assertSame(ExportFormat::XML, $this->strategy->getFormat());
    }

    public function testGenerateHeader(): void
    {
        $header = $this->strategy->generateHeader(['id', 'name']);

        $this->assertNotNull($header);
        $this->assertStringContainsString('<?xml version="1.0" encoding="UTF-8"?>', $header);
        $this->assertStringContainsString('<data>', $header);
    }

    public function testFormatRow(): void
    {
        $this->strategy->generateHeader(['id', 'name']);

        $data = ['id' => 1, 'name' => 'John Doe'];
        $row = $this->strategy->formatRow($data);

        $this->assertStringContainsString('<item>', $row);
        $this->assertStringContainsString('</item>', $row);
        $this->assertStringContainsString('<id>1</id>', $row);
        $this->assertStringContainsString('<name>John Doe</name>', $row);
    }

    public function testFormatRowWithSpecialCharacters(): void
    {
        $this->strategy->generateHeader(['text']);

        $data = ['text' => 'Hello "World" & <tag>'];
        $row = $this->strategy->formatRow($data);

        $this->assertStringContainsString('<text>', $row);
        // XMLWriter automatically escapes special characters
        $this->assertStringContainsString('&amp;', $row);
        $this->assertStringContainsString('&lt;', $row);
        $this->assertStringContainsString('&gt;', $row);
    }

    public function testFormatRowSanitizesInvalidTagNames(): void
    {
        $this->strategy->generateHeader(['valid-name', '123invalid', 'with spaces']);

        $data = [
            'valid-name' => 'value1',
            '123invalid' => 'value2',
            'with spaces' => 'value3',
        ];
        $row = $this->strategy->formatRow($data);

        $this->assertStringContainsString('<valid-name>value1</valid-name>', $row);
        $this->assertStringContainsString('<_123invalid>value2</_123invalid>', $row);
        $this->assertStringContainsString('<with_spaces>value3</with_spaces>', $row);
    }

    public function testGenerateFooter(): void
    {
        $footer = $this->strategy->generateFooter();

        $this->assertNotNull($footer);
        $this->assertStringContainsString('</data>', $footer);
    }

    public function testCustomRootAndItemElements(): void
    {
        $strategy = new XmlExportStrategy(rootElement: 'users', itemElement: 'user');

        $header = $strategy->generateHeader(['id']);
        $this->assertNotNull($header);
        $this->assertStringContainsString('<users>', $header);

        $row = $strategy->formatRow(['id' => 1]);
        $this->assertStringContainsString('<user>', $row);
        $this->assertStringContainsString('</user>', $row);

        $footer = $strategy->generateFooter();
        $this->assertNotNull($footer);
        $this->assertStringContainsString('</users>', $footer);
    }

    public function testPrettyPrintMode(): void
    {
        $strategy = new XmlExportStrategy(prettyPrint: true);

        $strategy->generateHeader(['id', 'name']);
        $row = $strategy->formatRow(['id' => 1, 'name' => 'Test']);

        // In pretty print mode, should have indentation
        $this->assertStringContainsString('  <item>', $row);
    }

    public function testHandlesNonScalarValues(): void
    {
        $this->strategy->generateHeader(['field']);

        $data = ['field' => ['array' => 'value']];
        $row = $this->strategy->formatRow($data);

        // Arrays should be JSON encoded (with XML entity escaping)
        $this->assertStringContainsString('{&quot;array&quot;:&quot;value&quot;}', $row);
        $this->assertStringContainsString('<field>', $row);
        $this->assertStringContainsString('</field>', $row);
    }

    public function testCompleteXmlDocument(): void
    {
        $header = $this->strategy->generateHeader(['id', 'name']);
        $row1 = $this->strategy->formatRow(['id' => 1, 'name' => 'Alice']);
        $row2 = $this->strategy->formatRow(['id' => 2, 'name' => 'Bob']);
        $footer = $this->strategy->generateFooter();

        $xml = $header . $row1 . $row2 . $footer;

        // Validate XML is well-formed
        libxml_use_internal_errors(true);
        $parsed = simplexml_load_string($xml);

        $this->assertNotFalse($parsed, 'Generated XML should be well-formed');
        $this->assertCount(2, $parsed->item);
    }
}
