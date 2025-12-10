<?php

declare(strict_types=1);

namespace Ecourty\DoctrineExportBundle\Tests\Integration;

use Ecourty\DoctrineExportBundle\Contract\DoctrineExporterInterface;
use Ecourty\DoctrineExportBundle\Enum\ExportFormat;
use Ecourty\DoctrineExportBundle\Exception\InvalidCriteriaException;
use Ecourty\DoctrineExportBundle\Tests\Fixtures\Entity\User;

class CsvExportIntegrationTest extends IntegrationTestCase
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
        $filePath = sys_get_temp_dir() . '/test_users_' . uniqid() . '.csv';

        $this->exporter->exportToFile(
            entityClass: User::class,
            format: ExportFormat::CSV,
            filePath: $filePath
        );

        $this->assertFileExists($filePath);

        $content = file_get_contents($filePath);
        $this->assertNotFalse($content);

        // Parse CSV properly to count records
        $handle = fopen($filePath, 'r');
        $this->assertNotFalse($handle);

        $recordCount = 0;
        while (($data = fgetcsv($handle)) !== false) {
            ++$recordCount;
        }
        fclose($handle);

        $this->assertSame(7, $recordCount); // 1 header + 6 users

        // Check header
        $this->assertStringContainsString('email', $content);
        $this->assertStringContainsString('firstName', $content);

        // Check first user
        $this->assertStringContainsString('john.doe@example.com', $content);

        // Check special characters are escaped
        $this->assertStringContainsString('"Name, with comma"', $content);
        $this->assertStringContainsString('"Last""name"""', $content);

        unlink($filePath);
    }

    public function testExportWithCriteria(): void
    {
        $filePath = sys_get_temp_dir() . '/test_active_users_' . uniqid() . '.csv';

        $this->exporter->exportToFile(
            entityClass: User::class,
            format: ExportFormat::CSV,
            filePath: $filePath,
            criteria: ['isActive' => true]
        );

        // Parse CSV to count records
        $handle = fopen($filePath, 'r');
        $this->assertNotFalse($handle);

        $recordCount = 0;
        while (($data = fgetcsv($handle)) !== false) {
            ++$recordCount;
        }
        fclose($handle);

        $this->assertSame(5, $recordCount); // 1 header + 4 active users

        unlink($filePath);
    }

    public function testExportWithLimitAndOffset(): void
    {
        $filePath = sys_get_temp_dir() . '/test_limited_users_' . uniqid() . '.csv';

        $this->exporter->exportToFile(
            entityClass: User::class,
            format: ExportFormat::CSV,
            filePath: $filePath,
            limit: 2,
            offset: 1
        );

        $content = file_get_contents($filePath);
        $this->assertNotFalse($content);

        $lines = explode("\n", trim($content));
        $this->assertCount(3, $lines); // 1 header + 2 users

        unlink($filePath);
    }

    public function testExportWithOrderBy(): void
    {
        $filePath = sys_get_temp_dir() . '/test_ordered_users_' . uniqid() . '.csv';

        $this->exporter->exportToFile(
            entityClass: User::class,
            format: ExportFormat::CSV,
            filePath: $filePath,
            orderBy: ['age' => 'ASC']
        );

        $content = file_get_contents($filePath);
        $this->assertNotFalse($content);

        $lines = explode("\n", trim($content));

        // Charlie (22) should be first after header
        $this->assertStringContainsString('charlie.brown@example.com', $lines[1]);

        unlink($filePath);
    }

    public function testExportWithCustomOptions(): void
    {
        $filePath = sys_get_temp_dir() . '/test_options_users_' . uniqid() . '.csv';

        $this->exporter->exportToFile(
            entityClass: User::class,
            format: ExportFormat::CSV,
            filePath: $filePath,
            options: [
                DoctrineExporterInterface::OPTION_BOOLEAN_TO_INTEGER => false,
                DoctrineExporterInterface::OPTION_DATETIME_FORMAT => 'Y-m-d',
                DoctrineExporterInterface::OPTION_NULL_VALUE => 'N/A',
            ]
        );

        $content = file_get_contents($filePath);
        $this->assertNotFalse($content);

        // Check boolean as string
        $this->assertStringContainsString('true', $content);
        $this->assertStringContainsString('false', $content);

        // Check date format
        $this->assertStringContainsString('2024-01-15', $content);

        // Check null value
        $this->assertStringContainsString('N/A', $content);

        unlink($filePath);
    }

    public function testExportToGenerator(): void
    {
        $lines = [];
        foreach ($this->exporter->exportToGenerator(User::class, ExportFormat::CSV) as $line) {
            $lines[] = $line;
        }

        $this->assertCount(7, $lines); // 1 header + 6 users
        $this->assertStringContainsString('email', $lines[0]);
    }

    public function testExportWithSelectedFields(): void
    {
        $filePath = sys_get_temp_dir() . '/test_selected_fields_' . uniqid() . '.csv';

        $this->exporter->exportToFile(
            entityClass: User::class,
            format: ExportFormat::CSV,
            filePath: $filePath,
            fields: ['id', 'email', 'firstName']
        );

        $this->assertFileExists($filePath);

        // Parse CSV to check fields
        $handle = fopen($filePath, 'r');
        $this->assertNotFalse($handle);

        $header = fgetcsv($handle);
        $this->assertIsArray($header);
        $this->assertSame(['id', 'email', 'firstName'], $header);

        // Check first data row has only 3 fields
        $firstRow = fgetcsv($handle);
        $this->assertIsArray($firstRow);
        $this->assertCount(3, $firstRow);

        fclose($handle);
        unlink($filePath);
    }

    public function testExportWithInvalidFieldThrowsException(): void
    {
        $filePath = sys_get_temp_dir() . '/test_invalid_field_' . uniqid() . '.csv';

        $this->expectException(InvalidCriteriaException::class);
        $this->expectExceptionMessage('Field "invalidField" does not exist');

        $this->exporter->exportToFile(
            entityClass: User::class,
            format: ExportFormat::CSV,
            filePath: $filePath,
            fields: ['id', 'invalidField']
        );
    }
}
