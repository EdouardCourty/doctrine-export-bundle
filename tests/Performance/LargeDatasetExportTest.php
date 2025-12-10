<?php

declare(strict_types=1);

namespace Ecourty\DoctrineExportBundle\Tests\Performance;

use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\Tools\SchemaTool;
use Ecourty\DoctrineExportBundle\Contract\DoctrineExporterInterface;
use Ecourty\DoctrineExportBundle\Enum\ExportFormat;
use Ecourty\DoctrineExportBundle\Tests\App\TestKernel;
use Ecourty\DoctrineExportBundle\Tests\Fixtures\Entity\User;
use Ecourty\DoctrineExportBundle\Tests\Support\TestDataLoader;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;

/**
 * @group performance
 * @group stress
 */
class LargeDatasetExportTest extends KernelTestCase
{
    private const ENTITY_COUNT = 10_000;
    private const MEMORY_LIMIT_CSV_MB = 2.5;
    private const MEMORY_LIMIT_JSON_XML_MB = 1.0;
    private const TIME_LIMIT_SECONDS = 1.0;

    private EntityManagerInterface $entityManager;
    private DoctrineExporterInterface $exporter;
    private TestDataLoader $dataLoader;

    protected static function getKernelClass(): string
    {
        return TestKernel::class;
    }

    protected function setUp(): void
    {
        self::bootKernel();

        $entityManager = self::getContainer()->get('doctrine.orm.entity_manager');
        $this->assertInstanceOf(EntityManagerInterface::class, $entityManager);
        $this->entityManager = $entityManager;

        $exporter = self::getContainer()->get(DoctrineExporterInterface::class);
        $this->assertInstanceOf(DoctrineExporterInterface::class, $exporter);
        $this->exporter = $exporter;

        $this->dataLoader = new TestDataLoader($this->entityManager);

        $this->createSchema();
        $this->dataLoader->loadLargeDataset(self::ENTITY_COUNT);
    }

    protected function tearDown(): void
    {
        $this->dropSchema();
        parent::tearDown();
        $this->entityManager->close();
    }

    public function testExportLargeDatasetToCsvWithMemoryConstraint(): void
    {
        $filePath = sys_get_temp_dir() . '/test_large_' . uniqid() . '.csv';
        $memoryBefore = memory_get_usage(true);

        $startTime = microtime(true);

        $this->exporter->exportToFile(
            entityClass: User::class,
            format: ExportFormat::CSV,
            filePath: $filePath
        );

        $duration = microtime(true) - $startTime;
        $memoryAfter = memory_get_usage(true);
        $memoryUsedMB = ($memoryAfter - $memoryBefore) / 1024 / 1024;

        $this->assertFileExists($filePath);

        // Verify file content
        $lines = file($filePath, \FILE_IGNORE_NEW_LINES);
        $this->assertNotFalse($lines);
        $this->assertCount(self::ENTITY_COUNT + 1, $lines); // +1 for header

        // Performance assertions
        $this->assertLessThan(self::MEMORY_LIMIT_CSV_MB, $memoryUsedMB,
            sprintf('Memory usage (%.2f MB) exceeded limit of %.2f MB', $memoryUsedMB, self::MEMORY_LIMIT_CSV_MB)
        );

        $this->assertLessThan(self::TIME_LIMIT_SECONDS, $duration,
            sprintf('Export took %.3f seconds, expected less than %.3f seconds', $duration, self::TIME_LIMIT_SECONDS)
        );

        unlink($filePath);
    }

    public function testExportLargeDatasetToJsonWithStreaming(): void
    {
        $filePath = sys_get_temp_dir() . '/test_large_' . uniqid() . '.json';
        $memoryBefore = memory_get_usage(true);

        $startTime = microtime(true);

        $this->exporter->exportToFile(
            entityClass: User::class,
            format: ExportFormat::JSON,
            filePath: $filePath
        );

        $duration = microtime(true) - $startTime;
        $memoryAfter = memory_get_usage(true);
        $memoryUsedMB = ($memoryAfter - $memoryBefore) / 1024 / 1024;

        $this->assertFileExists($filePath);

        // Verify JSON structure
        $content = file_get_contents($filePath);
        $this->assertNotFalse($content);
        $data = json_decode($content, true);
        $this->assertIsArray($data);
        $this->assertCount(self::ENTITY_COUNT, $data);

        $this->assertLessThan(self::MEMORY_LIMIT_JSON_XML_MB, $memoryUsedMB,
            sprintf('Memory usage (%.2f MB) exceeded limit of %.2f MB', $memoryUsedMB, self::MEMORY_LIMIT_JSON_XML_MB)
        );

        $this->assertLessThan(self::TIME_LIMIT_SECONDS, $duration,
            sprintf('Export took %.3f seconds, expected less than %.3f seconds', $duration, self::TIME_LIMIT_SECONDS)
        );

        unlink($filePath);
    }

    public function testExportLargeDatasetToXmlWithStreaming(): void
    {
        $filePath = sys_get_temp_dir() . '/test_large_' . uniqid() . '.xml';
        $memoryBefore = memory_get_usage(true);

        $startTime = microtime(true);

        $this->exporter->exportToFile(
            entityClass: User::class,
            format: ExportFormat::XML,
            filePath: $filePath
        );

        $duration = microtime(true) - $startTime;
        $memoryAfter = memory_get_usage(true);
        $memoryUsedMB = ($memoryAfter - $memoryBefore) / 1024 / 1024;

        $this->assertFileExists($filePath);

        // Verify XML structure
        $content = file_get_contents($filePath);
        $this->assertNotFalse($content);
        $this->assertStringContainsString('<?xml version="1.0" encoding="UTF-8"?>', $content);
        $this->assertStringContainsString('<data>', $content);
        $this->assertStringContainsString('</data>', $content);

        // Count items in XML
        $itemCount = substr_count($content, '<item>');
        $this->assertSame(self::ENTITY_COUNT, $itemCount);

        $this->assertLessThan(self::MEMORY_LIMIT_JSON_XML_MB, $memoryUsedMB,
            sprintf('Memory usage (%.2f MB) exceeded limit of %.2f MB', $memoryUsedMB, self::MEMORY_LIMIT_JSON_XML_MB)
        );

        $this->assertLessThan(self::TIME_LIMIT_SECONDS, $duration,
            sprintf('Export took %.3f seconds, expected less than %.3f seconds', $duration, self::TIME_LIMIT_SECONDS)
        );

        unlink($filePath);
    }

    public function testExportLargeDatasetUsingGenerator(): void
    {
        $memoryBefore = memory_get_usage(true);
        $startTime = microtime(true);

        $lineCount = 0;
        foreach ($this->exporter->exportToGenerator(
            entityClass: User::class,
            format: ExportFormat::CSV
        ) as $line) {
            ++$lineCount;
            // Simulate processing (e.g., streaming to HTTP response)
            $this->assertNotEmpty($line);
        }

        $duration = microtime(true) - $startTime;
        $memoryAfter = memory_get_usage(true);
        $memoryUsedMB = ($memoryAfter - $memoryBefore) / 1024 / 1024;

        $this->assertSame(self::ENTITY_COUNT + 1, $lineCount); // +1 for header

        $this->assertLessThan(self::MEMORY_LIMIT_CSV_MB, $memoryUsedMB,
            sprintf('Generator memory usage (%.2f MB) exceeded limit of %.2f MB', $memoryUsedMB, self::MEMORY_LIMIT_CSV_MB)
        );

        $this->assertLessThan(self::TIME_LIMIT_SECONDS, $duration,
            sprintf('Export took %.3f seconds, expected less than %.3f seconds', $duration, self::TIME_LIMIT_SECONDS)
        );
    }

    private function createSchema(): void
    {
        $schemaTool = new SchemaTool($this->entityManager);
        $metadata = $this->entityManager->getMetadataFactory()->getAllMetadata();
        $schemaTool->createSchema($metadata);
    }

    private function dropSchema(): void
    {
        $schemaTool = new SchemaTool($this->entityManager);
        $metadata = $this->entityManager->getMetadataFactory()->getAllMetadata();
        $schemaTool->dropSchema($metadata);
    }
}
