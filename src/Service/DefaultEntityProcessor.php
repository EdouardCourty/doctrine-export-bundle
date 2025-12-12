<?php

declare(strict_types=1);

namespace Ecourty\DoctrineExportBundle\Service;

use Doctrine\Common\Collections\Collection;
use Doctrine\Persistence\ManagerRegistry;
use Doctrine\Persistence\Mapping\ClassMetadata;
use Ecourty\DoctrineExportBundle\Contract\DoctrineExporterInterface;
use Ecourty\DoctrineExportBundle\Contract\EntityProcessorInterface;
use Ecourty\DoctrineExportBundle\Exception\EntityNotFoundException;
use Ecourty\DoctrineExportBundle\Exception\InvalidCriteriaException;
use Symfony\Component\PropertyAccess\PropertyAccessorInterface;

/**
 * Default entity processor that extracts raw entity data.
 *
 * @internal
 */
final class DefaultEntityProcessor implements EntityProcessorInterface
{
    public function __construct(
        private readonly PropertyAccessorInterface $propertyAccessor,
        private readonly ValueNormalizer $valueNormalizer,
        private readonly ManagerRegistry $managerRegistry,
    ) {
    }

    /**
     * @param array<string, mixed> $data
     * @param array<string, mixed> $options
     *
     * @return array<string, mixed>
     */
    public function process(object $entity, array $data, array $options): array
    {
        $metadata = $this->getMetadataForObject($entity);
        $strictFields = $options[DoctrineExporterInterface::OPTION_STRICT_FIELDS] ?? false;

        foreach (array_keys($data) as $field) {
            if (!$metadata->hasField($field) && !$metadata->hasAssociation($field)) {
                if ($strictFields) {
                    throw new InvalidCriteriaException(
                        \sprintf(
                            'Field "%s" does not exist in entity "%s". Available fields: %s',
                            $field,
                            $metadata->getName(),
                            implode(', ', array_merge($metadata->getFieldNames(), $metadata->getAssociationNames()))
                        )
                    );
                }

                continue;
            }

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
}
