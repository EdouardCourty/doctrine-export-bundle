<?php

declare(strict_types=1);

namespace Ecourty\DoctrineExportBundle\Tests\Service;

use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\Mapping\ClassMetadata;
use Doctrine\Persistence\ManagerRegistry;
use Ecourty\DoctrineExportBundle\Enum\ExportFormat;
use Ecourty\DoctrineExportBundle\Exception\InvalidCriteriaException;
use Ecourty\DoctrineExportBundle\Service\DefaultEntityProcessor;
use Ecourty\DoctrineExportBundle\Service\DoctrineExporter;
use Ecourty\DoctrineExportBundle\Service\EntityProcessorChain;
use Ecourty\DoctrineExportBundle\Service\ExportOptionsResolver;
use Ecourty\DoctrineExportBundle\Service\ExportStrategyRegistry;
use Ecourty\DoctrineExportBundle\Service\ValueNormalizer;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Symfony\Component\PropertyAccess\PropertyAccessor;

class DoctrineExporterTest extends TestCase
{
    private DoctrineExporter $exporter;
    private ManagerRegistry&MockObject $managerRegistry;
    private ExportStrategyRegistry&MockObject $strategyRegistry;

    protected function setUp(): void
    {
        $this->managerRegistry = $this->createMock(ManagerRegistry::class);
        $this->strategyRegistry = $this->createMock(ExportStrategyRegistry::class);

        $propertyAccessor = new PropertyAccessor();
        $optionsResolver = new ExportOptionsResolver();
        $valueNormalizer = new ValueNormalizer($optionsResolver);
        $defaultProcessor = new DefaultEntityProcessor($propertyAccessor, $valueNormalizer, $this->managerRegistry);
        $processorChain = new EntityProcessorChain($defaultProcessor, $optionsResolver);

        $this->exporter = new DoctrineExporter(
            $this->managerRegistry,
            $this->strategyRegistry,
            $processorChain
        );
    }

    public function testInvalidCriteriaThrowsException(): void
    {
        $criteria = ['invalid_field' => 'value'];

        $metadata = $this->createMock(ClassMetadata::class);
        $metadata->method('hasField')->willReturn(false);
        $metadata->method('hasAssociation')->willReturn(false);
        $metadata->method('getName')->willReturn(\stdClass::class);
        $metadata->method('getFieldNames')->willReturn(['id', 'name', 'email']);
        $metadata->method('getAssociationNames')->willReturn([]);

        $manager = $this->createMock(EntityManagerInterface::class);
        $manager->method('getClassMetadata')->willReturn($metadata);

        $this->managerRegistry
            ->method('getManagerForClass')
            ->with(\stdClass::class)
            ->willReturn($manager);

        $this->expectException(InvalidCriteriaException::class);
        $this->expectExceptionMessage('Field "invalid_field" does not exist in entity');

        $generator = $this->exporter->exportToGenerator(
            \stdClass::class,
            ExportFormat::CSV,
            $criteria
        );

        // Force generator execution
        iterator_to_array($generator);
    }

    public function testInvalidOrderByThrowsException(): void
    {
        $orderBy = ['invalid_field' => 'ASC'];

        $metadata = $this->createMock(ClassMetadata::class);
        $metadata->method('hasField')->willReturn(false);
        $metadata->method('hasAssociation')->willReturn(false);
        $metadata->method('getName')->willReturn(\stdClass::class);
        $metadata->method('getFieldNames')->willReturn(['id', 'name', 'email']);
        $metadata->method('getAssociationNames')->willReturn([]);

        $manager = $this->createMock(EntityManagerInterface::class);
        $manager->method('getClassMetadata')->willReturn($metadata);

        $this->managerRegistry
            ->method('getManagerForClass')
            ->with(\stdClass::class)
            ->willReturn($manager);

        $this->expectException(InvalidCriteriaException::class);
        $this->expectExceptionMessage('Field "invalid_field" does not exist in entity');

        $generator = $this->exporter->exportToGenerator(
            \stdClass::class,
            ExportFormat::CSV,
            [],
            null,
            null,
            $orderBy
        );

        // Force generator execution
        iterator_to_array($generator);
    }
}
