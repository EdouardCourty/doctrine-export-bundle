<?php

declare(strict_types=1);

namespace Ecourty\DoctrineExportBundle\Service;

use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\Persistence\ManagerRegistry;
use Doctrine\Persistence\Mapping\ClassMetadata;
use Ecourty\DoctrineExportBundle\Contract\DoctrineExporterInterface;
use Ecourty\DoctrineExportBundle\Enum\ExportFormat;
use Ecourty\DoctrineExportBundle\Exception\EntityNotFoundException;
use Ecourty\DoctrineExportBundle\Exception\FileWriteException;
use Ecourty\DoctrineExportBundle\Exception\InvalidCriteriaException;
use Symfony\Component\PropertyAccess\PropertyAccessorInterface;

class DoctrineExporter implements DoctrineExporterInterface
{
    public function __construct(
        private readonly ManagerRegistry $managerRegistry,
        private readonly ExportStrategyRegistry $strategyRegistry,
        private readonly PropertyAccessorInterface $propertyAccessor,
        private readonly ValueNormalizer $valueNormalizer,
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
    ): void {
        $handle = @fopen($filePath, 'w');
        if (false === $handle) {
            throw new FileWriteException(\sprintf('Cannot open file "%s" for writing', $filePath));
        }

        try {
            foreach ($this->exportToGenerator($entityClass, $format, $criteria, $limit, $offset, $orderBy, $fields, $options) as $line) {
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
    ): \Generator {
        $strategy = $this->strategyRegistry->getStrategy($format);

        $selectedFields = null;

        foreach ($this->iterateEntities($entityClass, $criteria, $limit, $offset, $orderBy) as [$entity, $entityMetadata]) {
            if (null === $selectedFields) {
                $metadata = $entityMetadata;
                $selectedFields = $this->selectFields($fields, $metadata);
                $header = $strategy->generateHeader($selectedFields);
                if (null !== $header) {
                    yield $header;
                }
            }

            $data = $this->extractEntityData($entity, $selectedFields, $metadata, $options);
            yield $strategy->formatRow($data);
        }

        $footer = $strategy->generateFooter();
        if (null !== $footer) {
            yield $footer;
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

        $metadata = $manager->getClassMetadata($entityClass);

        $this->validateCriteria($metadata, $criteria);
        $this->validateOrderBy($metadata, $orderBy);

        $qb = $manager->createQueryBuilder();
        $qb->select('e')
            ->from($entityClass, 'e');

        foreach ($criteria as $field => $value) {
            if (null === $value) {
                $qb->andWhere($qb->expr()->isNull('e.' . $field));
            } else {
                $paramName = str_replace('.', '_', $field);
                $qb->andWhere($qb->expr()->eq('e.' . $field, ':' . $paramName))
                    ->setParameter($paramName, $value);
            }
        }

        foreach ($orderBy as $field => $direction) {
            $qb->addOrderBy('e.' . $field, $direction);
        }

        if (null !== $limit) {
            $qb->setMaxResults($limit);
        }

        if (null !== $offset) {
            $qb->setFirstResult($offset);
        }

        $query = $qb->getQuery();
        $iterator = $query->toIterable();

        /** @var object $entity */
        foreach ($iterator as $entity) {
            yield [$entity, $metadata];
            $manager->detach($entity);
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
        $allFields = $metadata->getFieldNames();

        // If no fields requested, return all
        if (empty($requestedFields)) {
            return $allFields;
        }

        // Validate that all requested fields exist
        foreach ($requestedFields as $field) {
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

        return $requestedFields;
    }

    /**
     * @param array<int, string>    $fields
     * @param ClassMetadata<object> $metadata
     * @param array<string, mixed>  $options
     *
     * @return array<string, mixed>
     */
    private function extractEntityData(object $entity, array $fields, ClassMetadata $metadata, array $options): array
    {
        $data = [];
        foreach ($fields as $field) {
            $value = $this->propertyAccessor->getValue($entity, $field);

            if ($metadata->hasAssociation($field)) {
                $value = $this->extractAssociationIdentifiers($value);
            } else {
                $value = $this->valueNormalizer->normalize($value, $options);
            }

            $data[$field] = $value;
        }

        return $data;
    }

    /**
     * @return int|string|array<int, int|string>|null
     */
    private function extractAssociationIdentifiers(mixed $value): int|string|array|null
    {
        if (null === $value) {
            return null;
        }

        if ($value instanceof Collection) {
            $identifiers = [];
            foreach ($value as $item) {
                if (is_object($item)) {
                    $relatedMetadata = $this->getMetadataForObject($item);
                    $identifierValues = $relatedMetadata->getIdentifierValues($item);
                    $identifier = $this->getSingleIdentifierValue($identifierValues);
                    if (null !== $identifier) {
                        $identifiers[] = $identifier;
                    }
                }
            }

            return $identifiers;
        }

        if (is_object($value)) {
            $relatedMetadata = $this->getMetadataForObject($value);
            $identifierValues = $relatedMetadata->getIdentifierValues($value);

            return $this->getSingleIdentifierValue($identifierValues);
        }

        return null;
    }

    /**
     * @return ClassMetadata<object>
     */
    private function getMetadataForObject(object $entity): ClassMetadata
    {
        $manager = $this->managerRegistry->getManagerForClass($entity::class);
        if (null === $manager) {
            throw new EntityNotFoundException(
                \sprintf('No manager found for entity "%s"', $entity::class)
            );
        }

        return $manager->getClassMetadata($entity::class);
    }

    /**
     * @param array<string, mixed> $identifierValues
     */
    private function getSingleIdentifierValue(array $identifierValues): int|string|null
    {
        if (empty($identifierValues)) {
            return null;
        }

        $value = reset($identifierValues);

        return is_scalar($value) ? (is_int($value) ? $value : (string) $value) : null;
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
