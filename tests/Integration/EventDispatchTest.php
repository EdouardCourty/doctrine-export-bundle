<?php

declare(strict_types=1);

namespace Ecourty\DoctrineExportBundle\Tests\Integration;

use Ecourty\DoctrineExportBundle\Contract\DoctrineExporterInterface;
use Ecourty\DoctrineExportBundle\Enum\ExportFormat;
use Ecourty\DoctrineExportBundle\Event\PostExportEvent;
use Ecourty\DoctrineExportBundle\Event\PreExportEvent;
use Ecourty\DoctrineExportBundle\Tests\Fixtures\Entity\User;
use Symfony\Component\EventDispatcher\EventDispatcherInterface as SymfonyEventDispatcherInterface;

class EventDispatchTest extends IntegrationTestCase
{
    private DoctrineExporterInterface $exporter;

    protected function setUp(): void
    {
        parent::setUp();
        $this->exporter = $this->getExporter();
    }

    public function testPreExportEventIsDispatched(): void
    {
        $this->createUsers(5);

        $eventDispatched = false;
        $capturedEvent = null;

        /** @var SymfonyEventDispatcherInterface $dispatcher */
        $dispatcher = $this->getContainer()->get('event_dispatcher');
        $dispatcher->addListener(PreExportEvent::class, function (PreExportEvent $event) use (&$eventDispatched, &$capturedEvent): void {
            $eventDispatched = true;
            $capturedEvent = $event;
        });

        $result = $this->exporter->exportToGenerator(
            User::class,
            ExportFormat::CSV,
            criteria: ['isActive' => true],
            limit: 10
        );

        iterator_to_array($result);

        self::assertTrue($eventDispatched, 'PreExportEvent should be dispatched');
        self::assertInstanceOf(PreExportEvent::class, $capturedEvent);
        self::assertSame(User::class, $capturedEvent->getEntityClass());
        self::assertSame(ExportFormat::CSV, $capturedEvent->getFormat());
        self::assertSame(['isActive' => true], $capturedEvent->getCriteria());
        self::assertSame(10, $capturedEvent->getLimit());
    }

    public function testPostExportEventIsDispatched(): void
    {
        $this->createUsers(3);

        $eventDispatched = false;
        $capturedEvent = null;

        /** @var SymfonyEventDispatcherInterface $dispatcher */
        $dispatcher = $this->getContainer()->get('event_dispatcher');
        $dispatcher->addListener(PostExportEvent::class, function (PostExportEvent $event) use (&$eventDispatched, &$capturedEvent): void {
            $eventDispatched = true;
            $capturedEvent = $event;
        });

        $result = $this->exporter->exportToGenerator(
            User::class,
            ExportFormat::JSON
        );

        iterator_to_array($result);

        self::assertTrue($eventDispatched, 'PostExportEvent should be dispatched');
        self::assertInstanceOf(PostExportEvent::class, $capturedEvent);
        self::assertSame(User::class, $capturedEvent->getEntityClass());
        self::assertSame(ExportFormat::JSON, $capturedEvent->getFormat());
        self::assertSame(3, $capturedEvent->getExportedCount());
        self::assertGreaterThan(0.0, $capturedEvent->getDurationInSeconds());
    }

    public function testBothEventsAreDispatchedInOrder(): void
    {
        $this->createUsers(2);

        $events = [];

        $listener = function (object $event) use (&$events): void {
            $events[] = $event::class;
        };

        /** @var SymfonyEventDispatcherInterface $dispatcher */
        $dispatcher = $this->getContainer()->get('event_dispatcher');
        $dispatcher->addListener(PreExportEvent::class, $listener);
        $dispatcher->addListener(PostExportEvent::class, $listener);

        $result = $this->exporter->exportToGenerator(
            User::class,
            ExportFormat::CSV
        );

        iterator_to_array($result);

        self::assertCount(2, $events);
        self::assertSame(PreExportEvent::class, $events[0]);
        self::assertSame(PostExportEvent::class, $events[1]);
    }

    public function testPostExportEventContainsCorrectCount(): void
    {
        $this->createUsers(7);

        $capturedEvent = null;

        /** @var SymfonyEventDispatcherInterface $dispatcher */
        $dispatcher = $this->getContainer()->get('event_dispatcher');
        $dispatcher->addListener(PostExportEvent::class, function (PostExportEvent $event) use (&$capturedEvent): void {
            $capturedEvent = $event;
        });

        $result = $this->exporter->exportToGenerator(
            User::class,
            ExportFormat::XML,
            limit: 5
        );

        iterator_to_array($result);

        self::assertNotNull($capturedEvent);
        self::assertSame(5, $capturedEvent->getExportedCount());
        self::assertGreaterThan(0.0, $capturedEvent->getDurationInSeconds());
    }

    public function testEventsAreDispatchedForEmptyExport(): void
    {
        $preEventDispatched = false;
        $postEventDispatched = false;
        $capturedPostEvent = null;

        /** @var SymfonyEventDispatcherInterface $dispatcher */
        $dispatcher = $this->getContainer()->get('event_dispatcher');
        $dispatcher->addListener(PreExportEvent::class, function () use (&$preEventDispatched): void {
            $preEventDispatched = true;
        });
        $dispatcher->addListener(PostExportEvent::class, function (PostExportEvent $event) use (&$postEventDispatched, &$capturedPostEvent): void {
            $postEventDispatched = true;
            $capturedPostEvent = $event;
        });

        $result = $this->exporter->exportToGenerator(
            User::class,
            ExportFormat::CSV
        );

        iterator_to_array($result);

        self::assertTrue($preEventDispatched);
        self::assertTrue($postEventDispatched);
        self::assertNotNull($capturedPostEvent);
        self::assertSame(0, $capturedPostEvent->getExportedCount());
        self::assertGreaterThanOrEqual(0.0, $capturedPostEvent->getDurationInSeconds());
    }

    public function testPostExportEventContainsReasonableDuration(): void
    {
        $this->createUsers(10);

        $capturedEvent = null;

        /** @var SymfonyEventDispatcherInterface $dispatcher */
        $dispatcher = $this->getContainer()->get('event_dispatcher');
        $dispatcher->addListener(PostExportEvent::class, function (PostExportEvent $event) use (&$capturedEvent): void {
            $capturedEvent = $event;
        });

        $startTime = microtime(true);
        $result = $this->exporter->exportToGenerator(
            User::class,
            ExportFormat::CSV
        );
        iterator_to_array($result);
        $actualDuration = microtime(true) - $startTime;

        self::assertNotNull($capturedEvent);
        self::assertGreaterThan(0.0, $capturedEvent->getDurationInSeconds());
        self::assertLessThanOrEqual($actualDuration, $capturedEvent->getDurationInSeconds());
        self::assertLessThan(1.0, $capturedEvent->getDurationInSeconds(), 'Duration should be less than 10 seconds for 10 users');
    }

    protected function loadFixtures(): void
    {
        // Don't load default fixtures for event tests
    }

    private function createUsers(int $count): void
    {
        for ($i = 1; $i <= $count; ++$i) {
            $user = new User(
                email: "user{$i}@example.com",
                firstName: 'User',
                lastName: (string) $i,
                isActive: true,
                age: 20 + $i,
                score: 85.0 + $i,
                createdAt: new \DateTime(),
                phone: '+3361234567' . $i,
                city: 'Paris',
                country: 'France',
                zipCode: '75001',
                loginCount: $i * 10,
                bio: "User {$i} biography",
                lastLoginAt: new \DateTime()
            );
            $this->entityManager->persist($user);
        }
        $this->entityManager->flush();
    }
}
