<?php

declare(strict_types=1);

namespace Ecourty\DoctrineExportBundle\Tests\Unit;

use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\Mapping\ClassMetadata;
use Doctrine\ORM\Query;
use Doctrine\ORM\QueryBuilder;
use Doctrine\Persistence\ManagerRegistry;
use Ecourty\DoctrineExportBundle\Contract\ExportStrategyInterface;
use Ecourty\DoctrineExportBundle\Enum\ExportFormat;
use Ecourty\DoctrineExportBundle\Service\DefaultEntityProcessor;
use Ecourty\DoctrineExportBundle\Service\DoctrineExporter;
use Ecourty\DoctrineExportBundle\Service\EntityProcessorChain;
use Ecourty\DoctrineExportBundle\Service\ExportOptionsResolver;
use Ecourty\DoctrineExportBundle\Service\ExportStrategyRegistry;
use Ecourty\DoctrineExportBundle\Service\ValueNormalizer;
use Ecourty\DoctrineExportBundle\Tests\Fixtures\Entity\SimpleEntity;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Symfony\Component\PropertyAccess\PropertyAccessor;

class DoctrineExporterWithoutEventsTest extends TestCase
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

        // Create exporter WITHOUT event dispatcher
        $this->exporter = new DoctrineExporter(
            $this->managerRegistry,
            $this->strategyRegistry,
            $processorChain,
            null // No event dispatcher
        );
    }

    public function testExportWorksWithoutEventDispatcher(): void
    {
        $entity = new SimpleEntity(1, 'Test');

        $metadata = $this->createMock(ClassMetadata::class);
        $metadata->method('hasField')->willReturn(true);
        $metadata->method('hasAssociation')->willReturn(false);
        $metadata->method('getName')->willReturn(SimpleEntity::class);
        $metadata->method('getFieldNames')->willReturn(['id', 'name']);
        $metadata->method('getAssociationNames')->willReturn([]);

        $query = $this->createMock(Query::class);
        $query->method('toIterable')->willReturn([$entity]);

        $qb = $this->createMock(QueryBuilder::class);
        $qb->method('select')->willReturnSelf();
        $qb->method('from')->willReturnSelf();
        $qb->method('andWhere')->willReturnSelf();
        $qb->method('setParameter')->willReturnSelf();
        $qb->method('addOrderBy')->willReturnSelf();
        $qb->method('setMaxResults')->willReturnSelf();
        $qb->method('setFirstResult')->willReturnSelf();
        $qb->method('expr')->willReturn(new Query\Expr());
        $qb->method('getQuery')->willReturn($query);

        $manager = $this->createMock(EntityManagerInterface::class);
        $manager->method('getClassMetadata')->willReturn($metadata);
        $manager->method('createQueryBuilder')->willReturn($qb);
        $manager->expects(self::once())->method('detach')->with($entity);

        $this->managerRegistry
            ->method('getManagerForClass')
            ->with(SimpleEntity::class)
            ->willReturn($manager);

        $strategy = $this->createMock(ExportStrategyInterface::class);
        $strategy->method('getFormat')->willReturn(ExportFormat::CSV);
        $strategy->method('generateHeader')->willReturn("id,name\n");
        $strategy->method('formatRow')->willReturn("1,Test\n");
        $strategy->method('generateFooter')->willReturn(null);

        $this->strategyRegistry
            ->method('getStrategy')
            ->with(ExportFormat::CSV)
            ->willReturn($strategy);

        $result = [];
        foreach ($this->exporter->exportToGenerator(SimpleEntity::class, ExportFormat::CSV) as $line) {
            $result[] = $line;
        }

        self::assertCount(2, $result);
        self::assertSame("id,name\n", $result[0]);
        self::assertSame("1,Test\n", $result[1]);
    }
}
