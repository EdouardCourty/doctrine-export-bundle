<?php

declare(strict_types=1);

namespace Ecourty\DoctrineExportBundle\Tests\Unit\Service;

use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\Mapping\ClassMetadata;
use Doctrine\Persistence\ManagerRegistry;
use Ecourty\DoctrineExportBundle\Service\DoctrineExporter;
use Ecourty\DoctrineExportBundle\Service\ExportOptionsResolver;
use Ecourty\DoctrineExportBundle\Service\ExportStrategyRegistry;
use Ecourty\DoctrineExportBundle\Service\ValueNormalizer;
use Ecourty\DoctrineExportBundle\Strategy\JsonExportStrategy;
use Ecourty\DoctrineExportBundle\Tests\Fixtures\TestObject\ArticleWithAuthor;
use Ecourty\DoctrineExportBundle\Tests\Fixtures\TestObject\ArticleWithNullAuthor;
use Ecourty\DoctrineExportBundle\Tests\Fixtures\TestObject\ArticleWithTags;
use Ecourty\DoctrineExportBundle\Tests\Fixtures\TestObject\AuthorWithId;
use Ecourty\DoctrineExportBundle\Tests\Fixtures\TestObject\TagWithId;
use PHPUnit\Framework\TestCase;
use Symfony\Component\PropertyAccess\PropertyAccessor;

class DoctrineExporterAssociationTest extends TestCase
{
    public function testExtractsManyToOneAssociationAsPrimaryKey(): void
    {
        $author = new AuthorWithId(42);
        $article = new ArticleWithAuthor($author);

        $metadata = $this->createMock(ClassMetadata::class);
        $metadata->method('hasAssociation')->willReturnCallback(
            fn (string $field) => $field === 'author'
        );
        $metadata->method('getIdentifierValues')->willReturn(['id' => 42]);

        $em = $this->createMock(EntityManagerInterface::class);
        $em->method('getClassMetadata')->willReturn($metadata);

        $registry = $this->createMock(ManagerRegistry::class);
        $registry->method('getManagerForClass')->willReturn($em);

        $strategyRegistry = new ExportStrategyRegistry([new JsonExportStrategy()]);
        $propertyAccessor = new PropertyAccessor();
        $valueNormalizer = new ValueNormalizer(new ExportOptionsResolver());

        $exporter = new DoctrineExporter($registry, $strategyRegistry, $propertyAccessor, $valueNormalizer);

        // Use reflection to test private method behavior indirectly
        $reflection = new \ReflectionClass($exporter);
        $method = $reflection->getMethod('extractEntityData');

        /** @var array<string, mixed> $result */
        $result = $method->invoke($exporter, $article, ['title', 'author'], $metadata, []);

        $this->assertSame('Test Article', $result['title']);
        $this->assertSame(42, $result['author']);
    }

    public function testExtractsManyToManyAssociationAsArrayOfPrimaryKeys(): void
    {
        $tag1 = new TagWithId(1);
        $tag2 = new TagWithId(2);
        $tag3 = new TagWithId(3);

        $article = new ArticleWithTags([$tag1, $tag2, $tag3]);

        $metadata = $this->createMock(ClassMetadata::class);
        $metadata->method('hasAssociation')->willReturnCallback(
            fn (string $field) => $field === 'tags'
        );
        $metadata->method('getIdentifierValues')->willReturnOnConsecutiveCalls(
            ['id' => 1],
            ['id' => 2],
            ['id' => 3]
        );

        $em = $this->createMock(EntityManagerInterface::class);
        $em->method('getClassMetadata')->willReturn($metadata);

        $registry = $this->createMock(ManagerRegistry::class);
        $registry->method('getManagerForClass')->willReturn($em);

        $strategyRegistry = new ExportStrategyRegistry([new JsonExportStrategy()]);
        $propertyAccessor = new PropertyAccessor();
        $valueNormalizer = new ValueNormalizer(new ExportOptionsResolver());

        $exporter = new DoctrineExporter($registry, $strategyRegistry, $propertyAccessor, $valueNormalizer);

        // Use reflection to test private method behavior indirectly
        $reflection = new \ReflectionClass($exporter);
        $method = $reflection->getMethod('extractEntityData');

        /** @var array<string, mixed> $result */
        $result = $method->invoke($exporter, $article, ['title', 'tags'], $metadata, []);

        $this->assertSame('Test Article', $result['title']);
        $this->assertIsArray($result['tags']);
        $this->assertSame([1, 2, 3], $result['tags']);
    }

    public function testHandlesNullAssociation(): void
    {
        $article = new ArticleWithNullAuthor();

        $metadata = $this->createMock(ClassMetadata::class);
        $metadata->method('hasAssociation')->willReturnCallback(
            fn (string $field) => $field === 'author'
        );

        $em = $this->createMock(EntityManagerInterface::class);
        $em->method('getClassMetadata')->willReturn($metadata);

        $registry = $this->createMock(ManagerRegistry::class);
        $registry->method('getManagerForClass')->willReturn($em);

        $strategyRegistry = new ExportStrategyRegistry([new JsonExportStrategy()]);
        $propertyAccessor = new PropertyAccessor();
        $valueNormalizer = new ValueNormalizer(new ExportOptionsResolver());

        $exporter = new DoctrineExporter($registry, $strategyRegistry, $propertyAccessor, $valueNormalizer);

        // Use reflection to test private method behavior
        $reflection = new \ReflectionClass($exporter);
        $method = $reflection->getMethod('extractEntityData');

        /** @var array<string, mixed> $result */
        $result = $method->invoke($exporter, $article, ['title', 'author'], $metadata, []);

        $this->assertSame('Test Article', $result['title']);
        $this->assertNull($result['author']);
    }
}
