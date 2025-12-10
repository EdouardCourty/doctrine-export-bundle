<?php

declare(strict_types=1);

namespace Ecourty\DoctrineExportBundle\Tests\Integration;

use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\Tools\SchemaTool;
use Ecourty\DoctrineExportBundle\Contract\DoctrineExporterInterface;
use Ecourty\DoctrineExportBundle\Tests\App\TestKernel;
use Ecourty\DoctrineExportBundle\Tests\Support\TestDataLoader;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;

abstract class IntegrationTestCase extends KernelTestCase
{
    protected EntityManagerInterface $entityManager;
    protected TestDataLoader $dataLoader;

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
        $this->loadFixtures();
    }

    protected function loadFixtures(): void
    {
        $this->dataLoader->loadUsers();
    }

    protected function getEntityManager(): EntityManagerInterface
    {
        return $this->entityManager;
    }

    protected function getExporter(): DoctrineExporterInterface
    {
        $exporter = self::getContainer()->get(DoctrineExporterInterface::class);
        $this->assertInstanceOf(DoctrineExporterInterface::class, $exporter);

        return $exporter;
    }

    protected function tearDown(): void
    {
        $this->dropSchema();

        parent::tearDown();

        // Avoid memory leaks
        $this->entityManager->close();
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
