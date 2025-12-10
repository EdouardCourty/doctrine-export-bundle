<?php

declare(strict_types=1);

namespace Ecourty\DoctrineExportBundle\Tests\Integration;

use Ecourty\DoctrineExportBundle\Contract\DoctrineExporterInterface;
use Ecourty\DoctrineExportBundle\Enum\ExportFormat;
use Ecourty\DoctrineExportBundle\Tests\Fixtures\Entity\User;

class XmlExportIntegrationTest extends IntegrationTestCase
{
    private DoctrineExporterInterface $exporter;

    protected function setUp(): void
    {
        parent::setUp();

        $exporter = self::getContainer()->get(DoctrineExporterInterface::class);
        $this->assertInstanceOf(DoctrineExporterInterface::class, $exporter);
        $this->exporter = $exporter;
    }

    public function testExportAllUsersToFile(): void
    {
        $filePath = sys_get_temp_dir() . '/test_users_' . uniqid() . '.xml';

        $this->exporter->exportToFile(
            entityClass: User::class,
            format: ExportFormat::XML,
            filePath: $filePath
        );

        $this->assertFileExists($filePath);

        $content = file_get_contents($filePath);
        $this->assertNotFalse($content);

        // Load XML
        $xml = simplexml_load_string($content);
        $this->assertNotFalse($xml);

        $this->assertCount(6, $xml->item);

        // Check first user
        $firstUser = $xml->item[0];
        $this->assertSame('john.doe@example.com', (string) $firstUser->email);
        $this->assertSame('John', (string) $firstUser->firstName);

        unlink($filePath);
    }

    public function testExportWithSpecialCharactersInXml(): void
    {
        $filePath = sys_get_temp_dir() . '/test_special_' . uniqid() . '.xml';

        $this->exporter->exportToFile(
            entityClass: User::class,
            format: ExportFormat::XML,
            filePath: $filePath,
            criteria: ['firstName' => 'Name, with comma']
        );

        $content = file_get_contents($filePath);
        $this->assertNotFalse($content);

        $xml = simplexml_load_string($content);
        $this->assertNotFalse($xml);

        // Special characters should be properly encoded
        $this->assertCount(1, $xml->item);
        $this->assertStringContainsString('Name, with comma', (string) $xml->item[0]->firstName);
        $this->assertStringContainsString('Last"name"', (string) $xml->item[0]->lastName);

        unlink($filePath);
    }

    public function testExportWithOrderBy(): void
    {
        $filePath = sys_get_temp_dir() . '/test_ordered_' . uniqid() . '.xml';

        $this->exporter->exportToFile(
            entityClass: User::class,
            format: ExportFormat::XML,
            filePath: $filePath,
            orderBy: ['age' => 'DESC']
        );

        $content = file_get_contents($filePath);
        $this->assertNotFalse($content);

        $xml = simplexml_load_string($content);
        $this->assertNotFalse($xml);

        // Bob (45) should be first
        $this->assertSame('bob.wilson@example.com', (string) $xml->item[0]->email);

        unlink($filePath);
    }

    public function testExportWithBooleanAsString(): void
    {
        $filePath = sys_get_temp_dir() . '/test_bool_' . uniqid() . '.xml';

        $this->exporter->exportToFile(
            entityClass: User::class,
            format: ExportFormat::XML,
            filePath: $filePath,
            limit: 1,
            options: [
                DoctrineExporterInterface::OPTION_BOOLEAN_TO_INTEGER => false,
            ]
        );

        $content = file_get_contents($filePath);
        $this->assertNotFalse($content);

        $xml = simplexml_load_string($content);
        $this->assertNotFalse($xml);

        $this->assertSame('true', (string) $xml->item[0]->isActive);

        unlink($filePath);
    }

    public function testExportValidatesXmlStructure(): void
    {
        $filePath = sys_get_temp_dir() . '/test_structure_' . uniqid() . '.xml';

        $this->exporter->exportToFile(
            entityClass: User::class,
            format: ExportFormat::XML,
            filePath: $filePath,
            limit: 1
        );

        $content = file_get_contents($filePath);
        $this->assertNotFalse($content);

        // Check XML declaration
        $this->assertStringStartsWith('<?xml version="1.0" encoding="UTF-8"?>', $content);

        // Check root element
        $this->assertStringContainsString('<data>', $content);
        $this->assertStringContainsString('</data>', $content);

        // Check item element
        $this->assertStringContainsString('<item>', $content);
        $this->assertStringContainsString('</item>', $content);

        unlink($filePath);
    }

    public function testExportToGenerator(): void
    {
        $output = '';
        foreach ($this->exporter->exportToGenerator(User::class, ExportFormat::XML) as $chunk) {
            $output .= $chunk;
        }

        $xml = simplexml_load_string($output);
        $this->assertNotFalse($xml);
        $this->assertCount(6, $xml->item);
    }

    public function testExportWithSelectedFields(): void
    {
        $filePath = sys_get_temp_dir() . '/test_selected_fields_' . uniqid() . '.xml';

        $this->exporter->exportToFile(
            entityClass: User::class,
            format: ExportFormat::XML,
            filePath: $filePath,
            limit: 1,
            fields: ['id', 'email', 'isActive']
        );

        $content = file_get_contents($filePath);
        $this->assertNotFalse($content);

        $xml = simplexml_load_string($content);
        $this->assertNotFalse($xml);

        $firstItem = $xml->item[0];
        $this->assertNotNull($firstItem->id);
        $this->assertNotNull($firstItem->email);
        $this->assertNotNull($firstItem->isActive);

        // Check excluded fields are not present
        $this->assertObjectNotHasProperty('firstName', $firstItem);
        $this->assertObjectNotHasProperty('lastName', $firstItem);

        unlink($filePath);
    }
}
