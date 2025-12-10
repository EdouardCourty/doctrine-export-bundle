<?php

declare(strict_types=1);

namespace Ecourty\DoctrineExportBundle\Tests\Support;

use Doctrine\ORM\EntityManagerInterface;
use Ecourty\DoctrineExportBundle\Tests\Fixtures\Entity\User;

class TestDataLoader
{
    public function __construct(
        private readonly EntityManagerInterface $entityManager,
    ) {
    }

    public function loadUsers(): void
    {
        $users = [
            new User(
                email: 'john.doe@example.com',
                firstName: 'John',
                lastName: 'Doe',
                isActive: true,
                age: 30,
                score: 95.5,
                createdAt: new \DateTime('2024-01-15 10:30:00'),
                phone: '+33612345678',
                city: 'Paris',
                country: 'France',
                zipCode: '75001',
                loginCount: 142,
                bio: 'Software developer passionate about clean code.',
                lastLoginAt: new \DateTime('2024-12-01 09:15:00')
            ),
            new User(
                email: 'jane.smith@example.com',
                firstName: 'Jane',
                lastName: 'Smith',
                isActive: false,
                age: 28,
                score: 87.3,
                createdAt: new \DateTime('2024-02-20 14:45:00'),
                phone: '+33698765432',
                city: 'Lyon',
                country: 'France',
                zipCode: '69001',
                loginCount: 87,
                bio: null,
                lastLoginAt: new \DateTime('2024-11-15 14:30:00')
            ),
            new User(
                email: 'bob.wilson@example.com',
                firstName: 'Bob',
                lastName: 'Wilson',
                isActive: true,
                age: 45,
                score: 92.1,
                createdAt: new \DateTime('2024-03-10 09:15:00'),
                phone: '+44207123456',
                city: 'London',
                country: 'UK',
                zipCode: 'SW1A1AA',
                loginCount: 256,
                bio: 'Project manager with 15 years of experience.',
                lastLoginAt: new \DateTime('2024-12-09 18:45:00')
            ),
            new User(
                email: 'alice.johnson@example.com',
                firstName: 'Alice',
                lastName: 'Johnson',
                isActive: true,
                age: 35,
                score: 98.7,
                createdAt: new \DateTime('2024-04-05 16:20:00'),
                phone: '+4915512345678',
                city: 'Berlin',
                country: 'Germany',
                zipCode: '10115',
                loginCount: 389,
                bio: 'UX designer, coffee addict.',
                lastLoginAt: new \DateTime('2024-12-10 08:00:00')
            ),
            new User(
                email: 'charlie.brown@example.com',
                firstName: 'Charlie',
                lastName: 'Brown',
                isActive: false,
                age: 22,
                score: 75.0,
                createdAt: new \DateTime('2024-05-12 11:00:00'),
                phone: '+34911234567',
                city: 'Madrid',
                country: 'Spain',
                zipCode: '28001',
                loginCount: 12,
                bio: 'Junior developer learning the ropes.',
                lastLoginAt: null
            ),
            // Add special characters to test CSV escaping
            new User(
                email: 'special@example.com',
                firstName: 'Name, with comma',
                lastName: 'Last"name"',
                isActive: true,
                age: 40,
                score: 88.8,
                createdAt: new \DateTime('2024-06-01 08:30:00'),
                phone: '+39021234567',
                city: 'Rome',
                country: 'Italy',
                zipCode: '00100',
                loginCount: 201,
                bio: "Bio with\nnewlines\nand \"quotes\"",
                lastLoginAt: new \DateTime('2024-12-08 12:30:00')
            ),
        ];

        foreach ($users as $user) {
            $this->entityManager->persist($user);
        }

        $this->entityManager->flush();
    }

    public function clear(): void
    {
        $this->entityManager->clear();
    }

    public function loadLargeDataset(int $count = 10_000): void
    {
        $batchSize = 500;
        $cities = ['Paris', 'London', 'Berlin', 'Madrid', 'Rome', 'Amsterdam', 'Brussels', 'Vienna', 'Prague', 'Warsaw'];
        $countries = ['France', 'UK', 'Germany', 'Spain', 'Italy', 'Netherlands', 'Belgium', 'Austria', 'Czech Republic', 'Poland'];

        for ($i = 0; $i < $count; ++$i) {
            $cityIndex = $i % \count($cities);

            $user = new User(
                email: sprintf('user%d@example.com', $i),
                firstName: sprintf('FirstName%d', $i),
                lastName: sprintf('LastName%d', $i),
                isActive: 0 === $i % 2,
                age: 20 + ($i % 50),
                score: 50.0 + ($i % 50),
                createdAt: new \DateTime(sprintf('2024-01-01 00:00:00 +%d hours', $i % 8760)),
                phone: sprintf('+336%08d', $i),
                city: $cities[$cityIndex],
                country: $countries[$cityIndex],
                zipCode: sprintf('%05d', 10000 + ($i % 90000)),
                loginCount: $i % 500,
                bio: 0 === $i % 10 ? null : sprintf('Bio for user %d with some text content', $i),
                lastLoginAt: 0 === $i % 5 ? null : new \DateTime(sprintf('2024-12-01 00:00:00 +%d hours', $i % 240))
            );

            $this->entityManager->persist($user);

            if (0 === ($i + 1) % $batchSize) {
                $this->entityManager->flush();
                $this->entityManager->clear();
            }
        }

        $this->entityManager->flush();
        $this->entityManager->clear();
    }
}
