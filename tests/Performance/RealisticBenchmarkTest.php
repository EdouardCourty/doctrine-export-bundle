<?php

declare(strict_types=1);

namespace Ecourty\DoctrineExportBundle\Tests\Performance;

use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\Tools\SchemaTool;
use Doctrine\Persistence\ManagerRegistry;
use Ecourty\DoctrineExportBundle\Contract\ExportStrategyInterface;
use Ecourty\DoctrineExportBundle\Enum\ExportFormat;
use Ecourty\DoctrineExportBundle\Service\DefaultEntityProcessor;
use Ecourty\DoctrineExportBundle\Service\DoctrineExporter;
use Ecourty\DoctrineExportBundle\Service\EntityProcessorChain;
use Ecourty\DoctrineExportBundle\Service\ExportOptionsResolver;
use Ecourty\DoctrineExportBundle\Service\ExportStrategyRegistry;
use Ecourty\DoctrineExportBundle\Service\ValueNormalizer;
use Ecourty\DoctrineExportBundle\Strategy\CsvExportStrategy;
use Ecourty\DoctrineExportBundle\Strategy\JsonExportStrategy;
use Ecourty\DoctrineExportBundle\Strategy\XmlExportStrategy;
use Ecourty\DoctrineExportBundle\Tests\App\TestKernel;
use Ecourty\DoctrineExportBundle\Tests\Fixtures\Entity\User;
use Ecourty\DoctrineExportBundle\Tests\Support\TestDataLoader;
use Psr\EventDispatcher\EventDispatcherInterface;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Symfony\Component\PropertyAccess\PropertyAccessorInterface;

/**
 * @group performance
 * @group benchmark
 * @group stress
 */
class RealisticBenchmarkTest extends KernelTestCase
{
    private const ENTITY_COUNT = 10_000;
    private const ITERATIONS = 3;
    private const MEMORY_LIMIT_MB = 1.0;
    private const TIME_LIMIT_SECONDS = 1.0;

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

    public function testRealisticBenchmarkCsv(): void
    {
        $stats = $this->runBenchmark(ExportFormat::CSV, new CsvExportStrategy());

        $this->assertLessThan(self::TIME_LIMIT_SECONDS, $stats['avgTime'], 'CSV export should be under 1 second');
        $this->assertLessThan(self::MEMORY_LIMIT_MB, $stats['avgMem'], 'CSV memory usage should stay under 1 MB');
    }

    public function testRealisticBenchmarkJson(): void
    {
        $stats = $this->runBenchmark(ExportFormat::JSON, new JsonExportStrategy());

        $this->assertLessThan(self::TIME_LIMIT_SECONDS, $stats['avgTime'], 'JSON export should be under 1 second');
        $this->assertLessThan(self::MEMORY_LIMIT_MB, $stats['avgMem'], 'JSON memory usage should stay under 1 MB');
    }

    public function testRealisticBenchmarkXml(): void
    {
        $stats = $this->runBenchmark(ExportFormat::XML, new XmlExportStrategy());

        $this->assertLessThan(self::TIME_LIMIT_SECONDS, $stats['avgTime'], 'XML export should be under 1 second');
        $this->assertLessThan(self::MEMORY_LIMIT_MB, $stats['avgMem'], 'XML memory usage should stay under 1 MB');
    }

    /**
     * @return array{avgTime: float, avgMem: float, throughput: float}
     */
    private function runBenchmark(ExportFormat $format, ExportStrategyInterface $strategy): array
    {
        $managerRegistry = self::getContainer()->get('doctrine');
        $this->assertInstanceOf(ManagerRegistry::class, $managerRegistry);

        $propertyAccessor = self::getContainer()->get(PropertyAccessorInterface::class);
        $this->assertInstanceOf(PropertyAccessorInterface::class, $propertyAccessor);

        $times = [];
        $memory = [];

        for ($i = 0; $i < self::ITERATIONS; ++$i) {
            $registry = new ExportStrategyRegistry([$strategy]);
            $optionsResolver = new ExportOptionsResolver();
            $valueNormalizer = new ValueNormalizer($optionsResolver);
            $defaultProcessor = new DefaultEntityProcessor($propertyAccessor, $valueNormalizer, $managerRegistry);
            $processorChain = new EntityProcessorChain($defaultProcessor, $optionsResolver);
            /** @var EventDispatcherInterface $eventDispatcher */
            $eventDispatcher = self::getContainer()->get(EventDispatcherInterface::class);
            $exporter = new DoctrineExporter($managerRegistry, $registry, $processorChain, $eventDispatcher);

            $filePath = sys_get_temp_dir() . '/realistic_' . $format->value . '_' . uniqid() . '.' . $format->getExtension();

            gc_collect_cycles();
            $memBefore = memory_get_usage(true);
            $startTime = microtime(true);

            $exporter->exportToFile(
                entityClass: User::class,
                format: $format,
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

        $avgTime = array_sum($times) / \count($times);
        $avgMem = array_sum($memory) / \count($memory);
        $throughput = self::ENTITY_COUNT / $avgTime;

        return [
            'avgTime' => $avgTime,
            'avgMem' => $avgMem,
            'throughput' => $throughput,
        ];
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
