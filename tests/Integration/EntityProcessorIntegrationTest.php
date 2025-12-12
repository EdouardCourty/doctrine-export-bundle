<?php

declare(strict_types=1);

namespace Ecourty\DoctrineExportBundle\Tests\Integration;

use Ecourty\DoctrineExportBundle\Contract\DoctrineExporterInterface;
use Ecourty\DoctrineExportBundle\Enum\ExportFormat;
use Ecourty\DoctrineExportBundle\Exception\InvalidCriteriaException;
use Ecourty\DoctrineExportBundle\Tests\Fixtures\Entity\User;
use Ecourty\DoctrineExportBundle\Tests\Fixtures\Processor\BracketProcessor;
use Ecourty\DoctrineExportBundle\Tests\Fixtures\Processor\EmailMaskingProcessor;
use Ecourty\DoctrineExportBundle\Tests\Fixtures\Processor\FullyCustomProcessor;
use Ecourty\DoctrineExportBundle\Tests\Fixtures\Processor\UppercaseProcessor;
use Ecourty\DoctrineExportBundle\Tests\Fixtures\Processor\UserVirtualFieldsProcessor;

class EntityProcessorIntegrationTest extends IntegrationTestCase
{
    private DoctrineExporterInterface $exporter;

    protected function setUp(): void
    {
        parent::setUp();
        $this->exporter = $this->getExporter();
    }

    public function testExportWithCustomProcessor(): void
    {
        $filePath = sys_get_temp_dir() . '/test_processor_' . uniqid() . '.csv';

        $this->exporter->exportToFile(
            entityClass: User::class,
            format: ExportFormat::CSV,
            filePath: $filePath,
            fields: ['firstName', 'email'],
            processors: [new EmailMaskingProcessor()]
        );

        $this->assertFileExists($filePath);

        $content = file_get_contents($filePath);
        $this->assertIsString($content);
        $this->assertStringContainsString('firstName,email', $content);
        $this->assertStringContainsString('@example.com', $content);
        $this->assertStringNotContainsString('john@example.com', $content);

        unlink($filePath);
    }

    public function testExportWithVirtualFields(): void
    {
        $filePath = sys_get_temp_dir() . '/test_virtual_fields_' . uniqid() . '.json';

        $this->exporter->exportToFile(
            entityClass: User::class,
            format: ExportFormat::JSON,
            filePath: $filePath,
            fields: ['firstName', 'displayName', 'ageCategory'],
            processors: [new UserVirtualFieldsProcessor()]
        );

        $this->assertFileExists($filePath);

        $content = file_get_contents($filePath);
        $this->assertIsString($content);
        /** @var array<int, array<string, mixed>> $data */
        $data = json_decode($content, true);

        $this->assertArrayHasKey('displayName', $data[0]);
        $this->assertArrayHasKey('ageCategory', $data[0]);

        unlink($filePath);
    }

    public function testExportWithStrictFieldsValidation(): void
    {
        $this->expectException(InvalidCriteriaException::class);
        $this->expectExceptionMessage('Field "nonExistentField" does not exist');

        $filePath = sys_get_temp_dir() . '/test_strict_' . uniqid() . '.csv';

        $this->exporter->exportToFile(
            entityClass: User::class,
            format: ExportFormat::CSV,
            filePath: $filePath,
            fields: ['firstName', 'nonExistentField'],
            options: [
                DoctrineExporterInterface::OPTION_STRICT_FIELDS => true,
            ]
        );
    }

    public function testExportWithMultipleProcessors(): void
    {
        $filePath = sys_get_temp_dir() . '/test_multiple_' . uniqid() . '.json';

        $this->exporter->exportToFile(
            entityClass: User::class,
            format: ExportFormat::JSON,
            filePath: $filePath,
            fields: ['firstName'],
            processors: [
                new UppercaseProcessor(),
                new BracketProcessor(),
            ]
        );

        $this->assertFileExists($filePath);

        $content = file_get_contents($filePath);
        $this->assertIsString($content);
        /** @var array<int, array<string, mixed>> $data */
        $data = json_decode($content, true);

        $this->assertGreaterThan(0, count($data));

        /** @var array<int, mixed> $firstNames */
        $firstNames = array_column($data, 'firstName');
        foreach ($firstNames as $firstName) {
            $this->assertIsString($firstName);
            $this->assertStringStartsWith('[', $firstName);
            $this->assertStringEndsWith(']', $firstName);
            $this->assertMatchesRegularExpression('/^\[[A-Z, ]+\]$/', $firstName);
        }

        unlink($filePath);
    }

    public function testExportWithDisabledDefaultProcessor(): void
    {
        $filePath = sys_get_temp_dir() . '/test_disabled_default_' . uniqid() . '.json';

        $this->exporter->exportToFile(
            entityClass: User::class,
            format: ExportFormat::JSON,
            filePath: $filePath,
            fields: ['firstName', 'email', 'age'],
            options: [
                DoctrineExporterInterface::OPTION_DISABLE_DEFAULT_PROCESSOR => true,
            ],
            processors: [new FullyCustomProcessor()]
        );

        $this->assertFileExists($filePath);

        $content = file_get_contents($filePath);
        $this->assertIsString($content);
        /** @var array<int, array<string, mixed>> $data */
        $data = json_decode($content, true);

        $this->assertGreaterThan(0, count($data));
        $firstName = $data[0]['firstName'] ?? null;
        $email = $data[0]['email'] ?? null;
        $age = $data[0]['age'] ?? null;

        $this->assertIsString($firstName);
        $this->assertIsString($email);
        $this->assertIsInt($age);
        $this->assertStringStartsWith('CUSTOM_', $firstName);
        $this->assertStringStartsWith('CUSTOM_', $email);

        unlink($filePath);
    }
}
