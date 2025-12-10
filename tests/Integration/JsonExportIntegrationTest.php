<?php

declare(strict_types=1);

namespace Ecourty\DoctrineExportBundle\Tests\Integration;

use Ecourty\DoctrineExportBundle\Contract\DoctrineExporterInterface;
use Ecourty\DoctrineExportBundle\Enum\ExportFormat;
use Ecourty\DoctrineExportBundle\Tests\Fixtures\Entity\User;

class JsonExportIntegrationTest extends IntegrationTestCase
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
        $filePath = sys_get_temp_dir() . '/test_users_' . uniqid() . '.json';

        $this->exporter->exportToFile(
            entityClass: User::class,
            format: ExportFormat::JSON,
            filePath: $filePath
        );

        $this->assertFileExists($filePath);

        $content = file_get_contents($filePath);
        $this->assertNotFalse($content);

        $data = json_decode($content, true);
        $this->assertIsArray($data);
        $this->assertCount(6, $data);

        // Check first user structure
        $this->assertIsArray($data[0]);
        $this->assertArrayHasKey('email', $data[0]);
        $this->assertArrayHasKey('firstName', $data[0]);
        $this->assertArrayHasKey('isActive', $data[0]);

        $this->assertSame('john.doe@example.com', $data[0]['email']);
        $this->assertSame(1, $data[0]['isActive']); // Boolean as integer by default

        unlink($filePath);
    }

    public function testExportWithBooleanAsString(): void
    {
        $filePath = sys_get_temp_dir() . '/test_bool_string_' . uniqid() . '.json';

        $this->exporter->exportToFile(
            entityClass: User::class,
            format: ExportFormat::JSON,
            filePath: $filePath,
            options: [
                DoctrineExporterInterface::OPTION_BOOLEAN_TO_INTEGER => false,
            ]
        );

        $content = file_get_contents($filePath);
        $this->assertNotFalse($content);
        $data = json_decode($content, true);
        $this->assertIsArray($data);
        $this->assertIsArray($data[0]);
        $this->assertIsArray($data[1]);

        $this->assertSame('true', $data[0]['isActive']);
        $this->assertSame('false', $data[1]['isActive']);

        unlink($filePath);
    }

    public function testExportWithCriteriaAndLimit(): void
    {
        $filePath = sys_get_temp_dir() . '/test_filtered_' . uniqid() . '.json';

        $this->exporter->exportToFile(
            entityClass: User::class,
            format: ExportFormat::JSON,
            filePath: $filePath,
            criteria: ['isActive' => true],
            limit: 2
        );

        $content = file_get_contents($filePath);
        $this->assertNotFalse($content);
        $data = json_decode($content, true);
        $this->assertIsArray($data);

        $this->assertCount(2, $data);
        $this->assertIsArray($data[0]);
        $this->assertIsArray($data[1]);
        $this->assertSame(1, $data[0]['isActive']);
        $this->assertSame(1, $data[1]['isActive']);

        unlink($filePath);
    }

    public function testExportWithDateTimeFormat(): void
    {
        $filePath = sys_get_temp_dir() . '/test_datetime_' . uniqid() . '.json';

        $this->exporter->exportToFile(
            entityClass: User::class,
            format: ExportFormat::JSON,
            filePath: $filePath,
            limit: 1,
            options: [
                DoctrineExporterInterface::OPTION_DATETIME_FORMAT => 'Y-m-d H:i:s',
            ]
        );

        $content = file_get_contents($filePath);
        $this->assertNotFalse($content);
        $data = json_decode($content, true);
        $this->assertIsArray($data);
        $this->assertIsArray($data[0]);

        $createdAt = $data[0]['createdAt'];
        $this->assertIsString($createdAt);
        $this->assertMatchesRegularExpression('/^\d{4}-\d{2}-\d{2} \d{2}:\d{2}:\d{2}$/', $createdAt);

        unlink($filePath);
    }

    public function testExportHandlesNullValues(): void
    {
        $filePath = sys_get_temp_dir() . '/test_nulls_' . uniqid() . '.json';

        $this->exporter->exportToFile(
            entityClass: User::class,
            format: ExportFormat::JSON,
            filePath: $filePath,
            criteria: ['bio' => null]
        );

        $content = file_get_contents($filePath);
        $this->assertNotFalse($content);
        $data = json_decode($content, true);
        $this->assertIsArray($data);

        $this->assertCount(1, $data); // Jane Smith has null bio
        $this->assertIsArray($data[0]);
        $this->assertNull($data[0]['bio']);

        unlink($filePath);
    }

    public function testExportToGenerator(): void
    {
        $output = '';
        foreach ($this->exporter->exportToGenerator(User::class, ExportFormat::JSON) as $chunk) {
            $output .= $chunk;
        }

        $data = json_decode($output, true);
        $this->assertIsArray($data);
        $this->assertCount(6, $data);
    }

    public function testExportWithSelectedFields(): void
    {
        $filePath = sys_get_temp_dir() . '/test_selected_fields_' . uniqid() . '.json';

        $this->exporter->exportToFile(
            entityClass: User::class,
            format: ExportFormat::JSON,
            filePath: $filePath,
            limit: 1,
            fields: ['email', 'firstName', 'age']
        );

        $content = file_get_contents($filePath);
        $this->assertIsString($content);

        $data = json_decode($content, true);
        $this->assertIsArray($data);
        $this->assertCount(1, $data);

        // Check only selected fields are present
        $this->assertIsArray($data[0]);
        $this->assertArrayHasKey('email', $data[0]);
        $this->assertArrayHasKey('firstName', $data[0]);
        $this->assertArrayHasKey('age', $data[0]);
        $this->assertArrayNotHasKey('lastName', $data[0]);
        $this->assertArrayNotHasKey('bio', $data[0]);

        unlink($filePath);
    }
}
