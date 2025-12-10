<?php

declare(strict_types=1);

namespace Ecourty\DoctrineExportBundle\Tests\Performance;

use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\Tools\SchemaTool;
use Ecourty\DoctrineExportBundle\Enum\ExportFormat;
use Ecourty\DoctrineExportBundle\Service\DoctrineExporter;
use Ecourty\DoctrineExportBundle\Service\ExportOptionsResolver;
use Ecourty\DoctrineExportBundle\Service\ExportStrategyRegistry;
use Ecourty\DoctrineExportBundle\Service\ValueNormalizer;
use Ecourty\DoctrineExportBundle\Strategy\XmlExportStrategy;
use Ecourty\DoctrineExportBundle\Tests\App\TestKernel;
use Ecourty\DoctrineExportBundle\Tests\Fixtures\Entity\User;
use Ecourty\DoctrineExportBundle\Tests\Support\TestDataLoader;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Symfony\Component\PropertyAccess\PropertyAccessorInterface;

/**
 * @group performance
 * @group benchmark
 * @group stress
 */
class XmlRealisticBenchmarkTest extends KernelTestCase
{
    private const ENTITY_COUNT = 10_000;
    private const ITERATIONS = 3;

    private EntityManagerInterface $entityManager;
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

        $this->dataLoader = new TestDataLoader($this->entityManager);

        $this->createSchema();
        $this->loadRealisticDataset();
    }

    protected function tearDown(): void
    {
        $this->dropSchema();
        parent::tearDown();
        $this->entityManager->close();
    }

    public function testRealisticBenchmarkXmlWithXMLWriter(): void
    {
        $managerRegistry = self::getContainer()->get('doctrine');
        $this->assertInstanceOf(\Doctrine\Persistence\ManagerRegistry::class, $managerRegistry);

        $propertyAccessor = self::getContainer()->get(PropertyAccessorInterface::class);
        $this->assertInstanceOf(PropertyAccessorInterface::class, $propertyAccessor);

        // Benchmark XML export with XMLWriter
        $times = [];
        $memory = [];

        for ($i = 0; $i < self::ITERATIONS; ++$i) {
            $registry = new ExportStrategyRegistry([new XmlExportStrategy()]);
            $valueNormalizer = new ValueNormalizer(new ExportOptionsResolver());
            $exporter = new DoctrineExporter($managerRegistry, $registry, $propertyAccessor, $valueNormalizer);

            $filePath = sys_get_temp_dir() . '/realistic_xml_' . uniqid() . '.xml';

            gc_collect_cycles();
            $memBefore = memory_get_usage(true);
            $startTime = microtime(true);

            $exporter->exportToFile(
                entityClass: User::class,
                format: ExportFormat::XML,
                filePath: $filePath
            );

            $duration = microtime(true) - $startTime;
            $memAfter = memory_get_usage(true);
            $memUsed = ($memAfter - $memBefore) / 1024 / 1024;

            $times[] = $duration;
            $memory[] = $memUsed;

            $this->assertFileExists($filePath);
            $fileSize = filesize($filePath);
            $this->assertGreaterThan(0, $fileSize);

            unlink($filePath);
        }

        // Calculate statistics
        $avgTime = array_sum($times) / \count($times);
        $avgMem = array_sum($memory) / \count($memory);
        $throughput = self::ENTITY_COUNT / $avgTime;

        // Assertions
        $this->assertLessThan(1.0, $avgTime, 'Export should be under 1 second');
        $this->assertLessThan(5.0, $avgMem, 'Memory usage should stay under 5 MB');
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

    private function loadRealisticDataset(): void
    {
        $this->dataLoader->loadLargeDataset(self::ENTITY_COUNT);
    }
}
