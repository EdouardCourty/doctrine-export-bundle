<?php

declare(strict_types=1);

namespace Ecourty\DoctrineExportBundle\Tests\Integration;

use Ecourty\DoctrineExportBundle\Contract\DoctrineExporterInterface;
use Ecourty\DoctrineExportBundle\Enum\ExportFormat;
use Ecourty\DoctrineExportBundle\Tests\Fixtures\Entity\User;
use Ecourty\DoctrineExportBundle\Tests\Fixtures\Enum\UserStatus;

final class EnumExportTest extends IntegrationTestCase
{
    private DoctrineExporterInterface $exporter;

    protected function loadFixtures(): void
    {
        // Do not load default fixtures
    }

    protected function setUp(): void
    {
        parent::setUp();
        $exporter = self::getContainer()->get(DoctrineExporterInterface::class);
        $this->assertInstanceOf(DoctrineExporterInterface::class, $exporter);
        $this->exporter = $exporter;
    }

    public function testCsvExportWithBackedEnum(): void
    {
        $user = new User(
            email: 'user@example.com',
            firstName: 'John',
            lastName: 'Doe',
            isActive: true,
            age: 30,
            score: 85.5,
            createdAt: new \DateTime('2024-01-01 10:00:00'),
            phone: '+33123456789',
            city: 'Paris',
            country: 'France',
            zipCode: '75001',
            loginCount: 5,
            status: UserStatus::SUSPENDED,
        );

        $this->entityManager->persist($user);
        $this->entityManager->flush();

        $generator = $this->exporter->exportToGenerator(
            entityClass: User::class,
            format: ExportFormat::CSV,
        );

        $result = '';
        foreach ($generator as $line) {
            $result .= $line;
        }

        self::assertStringContainsString('status', $result);
        self::assertStringContainsString('suspended', $result);
        self::assertStringNotContainsString('UserStatus', $result);
    }

    public function testJsonExportWithBackedEnum(): void
    {
        $activeUser = new User(
            email: 'active@example.com',
            firstName: 'Jane',
            lastName: 'Smith',
            isActive: true,
            age: 25,
            score: 90.0,
            createdAt: new \DateTime('2024-01-15 14:00:00'),
            phone: '+33987654321',
            city: 'Lyon',
            country: 'France',
            zipCode: '69001',
            loginCount: 10,
            status: UserStatus::ACTIVE,
        );

        $pendingUser = new User(
            email: 'pending@example.com',
            firstName: 'Bob',
            lastName: 'Martin',
            isActive: false,
            age: 28,
            score: 75.0,
            createdAt: new \DateTime('2024-02-01 09:00:00'),
            phone: '+33555666777',
            city: 'Marseille',
            country: 'France',
            zipCode: '13001',
            loginCount: 0,
            status: UserStatus::PENDING,
        );

        $this->entityManager->persist($activeUser);
        $this->entityManager->persist($pendingUser);
        $this->entityManager->flush();

        $generator = $this->exporter->exportToGenerator(
            entityClass: User::class,
            format: ExportFormat::JSON,
        );

        $result = '';
        foreach ($generator as $line) {
            $result .= $line;
        }

        $data = json_decode($result, true);
        self::assertIsArray($data);
        self::assertCount(2, $data);

        $statuses = array_column($data, 'status');
        self::assertContains('active', $statuses);
        self::assertContains('pending', $statuses);
        self::assertNotContains('UserStatus', $statuses);
    }

    public function testXmlExportWithBackedEnum(): void
    {
        $user = new User(
            email: 'xml@example.com',
            firstName: 'Alice',
            lastName: 'Wonder',
            isActive: true,
            age: 35,
            score: 95.5,
            createdAt: new \DateTime('2024-03-01 12:00:00'),
            phone: '+33111222333',
            city: 'Nice',
            country: 'France',
            zipCode: '06000',
            loginCount: 20,
            status: UserStatus::INACTIVE,
        );

        $this->entityManager->persist($user);
        $this->entityManager->flush();

        $generator = $this->exporter->exportToGenerator(
            entityClass: User::class,
            format: ExportFormat::XML,
        );

        $result = '';
        foreach ($generator as $line) {
            $result .= $line;
        }

        self::assertStringContainsString('<status>inactive</status>', $result);
        self::assertStringNotContainsString('UserStatus', $result);
    }

    public function testExportWithMultipleEnumValues(): void
    {
        $statuses = [UserStatus::ACTIVE, UserStatus::INACTIVE, UserStatus::SUSPENDED, UserStatus::PENDING];

        foreach ($statuses as $index => $status) {
            $user = new User(
                email: "user{$index}@example.com",
                firstName: "User{$index}",
                lastName: 'Test',
                isActive: true,
                age: 30,
                score: 80.0,
                createdAt: new \DateTime(),
                phone: '+33100000000',
                city: 'Paris',
                country: 'France',
                zipCode: '75001',
                loginCount: 1,
                status: $status,
            );
            $this->entityManager->persist($user);
        }
        $this->entityManager->flush();

        $generator = $this->exporter->exportToGenerator(
            entityClass: User::class,
            format: ExportFormat::CSV,
        );

        $result = '';
        foreach ($generator as $line) {
            $result .= $line;
        }

        self::assertStringContainsString('active', $result);
        self::assertStringContainsString('inactive', $result);
        self::assertStringContainsString('suspended', $result);
        self::assertStringContainsString('pending', $result);
    }

    public function testExportEnumWithFieldsFilter(): void
    {
        $user = new User(
            email: 'filter@example.com',
            firstName: 'Filter',
            lastName: 'Test',
            isActive: true,
            age: 40,
            score: 88.0,
            createdAt: new \DateTime('2024-04-01 08:00:00'),
            phone: '+33444555666',
            city: 'Toulouse',
            country: 'France',
            zipCode: '31000',
            loginCount: 15,
            status: UserStatus::ACTIVE,
        );

        $this->entityManager->persist($user);
        $this->entityManager->flush();

        $generator = $this->exporter->exportToGenerator(
            entityClass: User::class,
            format: ExportFormat::JSON,
            fields: ['email', 'status'],
        );

        $result = '';
        foreach ($generator as $line) {
            $result .= $line;
        }

        $data = json_decode($result, true);
        self::assertIsArray($data);
        self::assertCount(1, $data);
        self::assertIsArray($data[0]);
        self::assertArrayHasKey('email', $data[0]);
        self::assertArrayHasKey('status', $data[0]);
        self::assertSame('active', $data[0]['status']);
        self::assertCount(2, $data[0]);
    }
}
