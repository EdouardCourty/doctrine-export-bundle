<?php

declare(strict_types=1);

namespace Ecourty\DoctrineExportBundle\Service;

use Doctrine\ORM\EntityManagerInterface;
use Doctrine\Persistence\ManagerRegistry;
use Doctrine\Persistence\Mapping\ClassMetadata;
use Ecourty\DoctrineExportBundle\Contract\DoctrineExporterInterface;
use Ecourty\DoctrineExportBundle\Enum\ExportFormat;
use Ecourty\DoctrineExportBundle\Event\PostExportEvent;
use Ecourty\DoctrineExportBundle\Event\PreExportEvent;
use Ecourty\DoctrineExportBundle\Exception\EntityNotFoundException;
use Ecourty\DoctrineExportBundle\Exception\FileWriteException;
use Ecourty\DoctrineExportBundle\Exception\InvalidCriteriaException;
use Psr\EventDispatcher\EventDispatcherInterface;

class DoctrineExporter implements DoctrineExporterInterface
{
    public function __construct(
        private readonly ManagerRegistry $managerRegistry,
        private readonly ExportStrategyRegistry $strategyRegistry,
        private readonly EntityProcessorChain $processorChain,
        private readonly ?EventDispatcherInterface $eventDispatcher = null,
    ) {
    }

    public function exportToFile(
        string $entityClass,
        ExportFormat $format,
        string $filePath,
        array $criteria = [],
        ?int $limit = null,
        ?int $offset = null,
        array $orderBy = [],
        array $fields = [],
        array $options = [],
        array $processors = [],
    ): void {
        $handle = @fopen($filePath, 'w');
        if (false === $handle) {
            throw new FileWriteException(\sprintf('Cannot open file "%s" for writing', $filePath));
        }

        try {
            foreach ($this->exportToGenerator($entityClass, $format, $criteria, $limit, $offset, $orderBy, $fields, $options, $processors) as $line) {
                fwrite($handle, $line);
            }
        } finally {
            fclose($handle);
        }
    }

    /**
     * @return \Generator<string>
     */
    public function exportToGenerator(
        string $entityClass,
        ExportFormat $format,
        array $criteria = [],
        ?int $limit = null,
        ?int $offset = null,
        array $orderBy = [],
        array $fields = [],
        array $options = [],
        array $processors = [],
    ): \Generator {
        $startTime = microtime(true);

        if (null !== $this->eventDispatcher) {
            $this->eventDispatcher->dispatch(new PreExportEvent(
                $entityClass,
                $format,
                $criteria,
                $limit,
                $offset,
                $orderBy,
                $fields,
                $options
            ));
        }

        $strategy = $this->strategyRegistry->getStrategy($format);

        $selectedFields = null;
        $exportedCount = 0;

        foreach ($this->iterateEntities($entityClass, $criteria, $limit, $offset, $orderBy) as [$entity, $entityMetadata]) {
            if (null === $selectedFields) {
                $metadata = $entityMetadata;
                $selectedFields = $this->selectFields($fields, $metadata);
                $header = $strategy->generateHeader($selectedFields);
                if (null !== $header) {
                    yield $header;
                }
            }

            $data = $this->processorChain->process($entity, $selectedFields, $options, $processors);
            yield $strategy->formatRow($data);
            ++$exportedCount;
            unset($data, $entity);
        }

        $footer = $strategy->generateFooter();
        if (null !== $footer) {
            yield $footer;
        }

        if (null !== $this->eventDispatcher) {
            $durationInSeconds = microtime(true) - $startTime;
            $this->eventDispatcher->dispatch(new PostExportEvent(
                $entityClass,
                $format,
                $criteria,
                $limit,
                $offset,
                $orderBy,
                $fields,
                $options,
                $exportedCount,
                $durationInSeconds
            ));
        }
    }

    /**
     * @param class-string          $entityClass
     * @param array<string, mixed>  $criteria
     * @param array<string, string> $orderBy
     *
     * @return \Generator<array{object, ClassMetadata<object>}>
     */
    private function iterateEntities(
        string $entityClass,
        array $criteria,
        ?int $limit,
        ?int $offset,
        array $orderBy,
    ): \Generator {
        $manager = $this->getEntityManager($entityClass);
        $metadata = $manager->getClassMetadata($entityClass);

        $this->validateCriteria($metadata, $criteria);
        $this->validateOrderBy($metadata, $orderBy);

        $query = $this->buildQuery($manager, $entityClass, $criteria, $limit, $offset, $orderBy);
        $iterator = $query->toIterable();

        /** @var object $entity */
        foreach ($iterator as $entity) {
            yield [$entity, $metadata];
            $manager->detach($entity);
        }
    }

    /**
     * @param class-string $entityClass
     */
    private function getEntityManager(string $entityClass): EntityManagerInterface
    {
        $manager = $this->managerRegistry->getManagerForClass($entityClass);
        if (null === $manager) {
            throw new EntityNotFoundException(
                \sprintf('No manager found for entity "%s"', $entityClass)
            );
        }

        if (!$manager instanceof EntityManagerInterface) {
            throw new EntityNotFoundException(
                \sprintf('Manager for entity "%s" is not an EntityManager', $entityClass)
            );
        }

        return $manager;
    }

    /**
     * @param class-string          $entityClass
     * @param array<string, mixed>  $criteria
     * @param array<string, string> $orderBy
     *
     * @return \Doctrine\ORM\Query<mixed>
     */
    private function buildQuery(
        EntityManagerInterface $manager,
        string $entityClass,
        array $criteria,
        ?int $limit,
        ?int $offset,
        array $orderBy,
    ): \Doctrine\ORM\Query {
        $qb = $manager->createQueryBuilder();
        $qb->select('e')
            ->from($entityClass, 'e');

        $this->applyCriteria($qb, $criteria);
        $this->applyOrderBy($qb, $orderBy);
        $this->applyPagination($qb, $limit, $offset);

        return $qb->getQuery();
    }

    /**
     * @param array<string, mixed> $criteria
     */
    private function applyCriteria(\Doctrine\ORM\QueryBuilder $qb, array $criteria): void
    {
        foreach ($criteria as $field => $value) {
            if (null === $value) {
                $qb->andWhere($qb->expr()->isNull('e.' . $field));
            } else {
                $paramName = str_replace('.', '_', $field);
                $qb->andWhere($qb->expr()->eq('e.' . $field, ':' . $paramName))
                    ->setParameter($paramName, $value);
            }
        }
    }

    /**
     * @param array<string, string> $orderBy
     */
    private function applyOrderBy(\Doctrine\ORM\QueryBuilder $qb, array $orderBy): void
    {
        foreach ($orderBy as $field => $direction) {
            $qb->addOrderBy('e.' . $field, $direction);
        }
    }

    private function applyPagination(\Doctrine\ORM\QueryBuilder $qb, ?int $limit, ?int $offset): void
    {
        if (null !== $limit) {
            $qb->setMaxResults($limit);
        }

        if (null !== $offset) {
            $qb->setFirstResult($offset);
        }
    }

    /**
     * @param array<int, string>    $requestedFields
     * @param ClassMetadata<object> $metadata
     *
     * @return array<int, string>
     */
    private function selectFields(array $requestedFields, ClassMetadata $metadata): array
    {
        if (empty($requestedFields)) {
            return $metadata->getFieldNames();
        }

        return $requestedFields;
    }

    /**
     * @param ClassMetadata<object> $metadata
     * @param array<string, mixed>  $criteria
     */
    private function validateCriteria(ClassMetadata $metadata, array $criteria): void
    {
        foreach (array_keys($criteria) as $field) {
            if (!$metadata->hasField($field) && !$metadata->hasAssociation($field)) {
                throw new InvalidCriteriaException(
                    \sprintf(
                        'Field "%s" does not exist in entity "%s". Available fields: %s',
                        $field,
                        $metadata->getName(),
                        implode(', ', array_merge($metadata->getFieldNames(), $metadata->getAssociationNames()))
                    )
                );
            }
        }
    }

    /**
     * @param ClassMetadata<object> $metadata
     * @param array<string, string> $orderBy
     */
    private function validateOrderBy(ClassMetadata $metadata, array $orderBy): void
    {
        foreach (array_keys($orderBy) as $field) {
            if (!$metadata->hasField($field) && !$metadata->hasAssociation($field)) {
                throw new InvalidCriteriaException(
                    \sprintf(
                        'Field "%s" does not exist in entity "%s". Available fields: %s',
                        $field,
                        $metadata->getName(),
                        implode(', ', array_merge($metadata->getFieldNames(), $metadata->getAssociationNames()))
                    )
                );
            }
        }
    }
}
