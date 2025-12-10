<?php

declare(strict_types=1);

namespace Ecourty\DoctrineExportBundle\Tests\Integration;

use Ecourty\DoctrineExportBundle\Enum\ExportFormat;
use Ecourty\DoctrineExportBundle\Tests\Fixtures\Entity\Article;
use Ecourty\DoctrineExportBundle\Tests\Fixtures\Entity\Tag;
use Ecourty\DoctrineExportBundle\Tests\Fixtures\Entity\User;

class AssociationExportTest extends IntegrationTestCase
{
    protected function loadFixtures(): void
    {
        // Do not load default fixtures
    }

    public function testExportWithManyToOneAssociation(): void
    {
        $em = $this->getEntityManager();
        $exporter = $this->getExporter();

        $user = new User(
            email: 'author@example.com',
            firstName: 'John',
            lastName: 'Doe',
            isActive: true,
            age: 30,
            score: 85.5,
            createdAt: new \DateTime('2024-01-01'),
            phone: '1234567890',
            city: 'Paris',
            country: 'France',
            zipCode: '75001',
            loginCount: 5
        );
        $em->persist($user);

        $article = new Article('Test Article', $user);
        $em->persist($article);
        $em->flush();

        $userId = $user->getId();
        $this->assertNotNull($userId);

        $em->clear();

        $result = $exporter->exportToGenerator(
            entityClass: Article::class,
            format: ExportFormat::JSON,
            fields: ['title', 'author']
        );

        $output = '';
        foreach ($result as $line) {
            $output .= $line;
        }

        $decoded = json_decode($output, true);
        $this->assertIsArray($decoded);
        $this->assertCount(1, $decoded);
        $this->assertIsArray($decoded[0]);
        $this->assertSame('Test Article', $decoded[0]['title']);
        $this->assertSame($userId, $decoded[0]['author']);
    }

    public function testExportWithManyToManyAssociation(): void
    {
        $em = $this->getEntityManager();
        $exporter = $this->getExporter();

        $user = new User(
            email: 'author@example.com',
            firstName: 'John',
            lastName: 'Doe',
            isActive: true,
            age: 30,
            score: 85.5,
            createdAt: new \DateTime('2024-01-01'),
            phone: '1234567890',
            city: 'Paris',
            country: 'France',
            zipCode: '75001',
            loginCount: 5
        );
        $em->persist($user);

        $tag1 = new Tag('PHP');
        $tag2 = new Tag('Symfony');
        $tag3 = new Tag('Doctrine');
        $em->persist($tag1);
        $em->persist($tag2);
        $em->persist($tag3);

        $article = new Article('Test Article', $user);
        $article->addTag($tag1);
        $article->addTag($tag2);
        $article->addTag($tag3);
        $em->persist($article);
        $em->flush();

        $tag1Id = $tag1->getId();
        $tag2Id = $tag2->getId();
        $tag3Id = $tag3->getId();
        $this->assertNotNull($tag1Id);
        $this->assertNotNull($tag2Id);
        $this->assertNotNull($tag3Id);

        $em->clear();

        $result = $exporter->exportToGenerator(
            entityClass: Article::class,
            format: ExportFormat::JSON,
            fields: ['title', 'tags']
        );

        $output = '';
        foreach ($result as $line) {
            $output .= $line;
        }

        $decoded = json_decode($output, true);
        $this->assertIsArray($decoded);
        $this->assertCount(1, $decoded);
        $this->assertIsArray($decoded[0]);
        $this->assertSame('Test Article', $decoded[0]['title']);
        $this->assertIsArray($decoded[0]['tags']);
        $this->assertCount(3, $decoded[0]['tags']);
        $this->assertContains($tag1Id, $decoded[0]['tags']);
        $this->assertContains($tag2Id, $decoded[0]['tags']);
        $this->assertContains($tag3Id, $decoded[0]['tags']);
    }

    public function testExportWithNullAssociation(): void
    {
        $em = $this->getEntityManager();
        $exporter = $this->getExporter();

        $user = new User(
            email: 'author@example.com',
            firstName: 'John',
            lastName: 'Doe',
            isActive: true,
            age: 30,
            score: 85.5,
            createdAt: new \DateTime('2024-01-01'),
            phone: '1234567890',
            city: 'Paris',
            country: 'France',
            zipCode: '75001',
            loginCount: 5
        );
        $em->persist($user);

        $article = new Article('Test Article', $user);
        $em->persist($article);
        $em->flush();
        $em->clear();

        $result = $exporter->exportToGenerator(
            entityClass: Article::class,
            format: ExportFormat::JSON,
            fields: ['title', 'tags']
        );

        $output = '';
        foreach ($result as $line) {
            $output .= $line;
        }

        $decoded = json_decode($output, true);
        $this->assertIsArray($decoded);
        $this->assertCount(1, $decoded);
        $this->assertIsArray($decoded[0]);
        $this->assertSame('Test Article', $decoded[0]['title']);
        $this->assertIsArray($decoded[0]['tags']);
        $this->assertEmpty($decoded[0]['tags']);
    }

    public function testExportWithAssociationInCsv(): void
    {
        $em = $this->getEntityManager();
        $exporter = $this->getExporter();

        $user = new User(
            email: 'author@example.com',
            firstName: 'John',
            lastName: 'Doe',
            isActive: true,
            age: 30,
            score: 85.5,
            createdAt: new \DateTime('2024-01-01'),
            phone: '1234567890',
            city: 'Paris',
            country: 'France',
            zipCode: '75001',
            loginCount: 5
        );
        $em->persist($user);

        $tag1 = new Tag('PHP');
        $tag2 = new Tag('Symfony');
        $em->persist($tag1);
        $em->persist($tag2);

        $article = new Article('Test Article', $user);
        $article->addTag($tag1);
        $article->addTag($tag2);
        $em->persist($article);
        $em->flush();

        $userId = $user->getId();
        $this->assertNotNull($userId);

        $em->clear();

        $result = $exporter->exportToGenerator(
            entityClass: Article::class,
            format: ExportFormat::CSV,
            fields: ['title', 'author', 'tags']
        );

        $output = '';
        foreach ($result as $line) {
            $output .= $line;
        }

        $lines = explode("\n", trim($output));
        $this->assertCount(2, $lines);
        $this->assertSame('title,author,tags', $lines[0]);
        $this->assertStringContainsString('Test Article', $lines[1]);
        $this->assertStringContainsString((string) $userId, $lines[1]);
    }
}
