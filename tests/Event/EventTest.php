<?php

declare(strict_types=1);

namespace Ecourty\DoctrineExportBundle\Tests\Event;

use Ecourty\DoctrineExportBundle\Enum\ExportFormat;
use Ecourty\DoctrineExportBundle\Event\PostExportEvent;
use Ecourty\DoctrineExportBundle\Event\PreExportEvent;
use Ecourty\DoctrineExportBundle\Tests\Fixtures\Entity\User;
use PHPUnit\Framework\TestCase;

class EventTest extends TestCase
{
    public function testPreExportEventGetters(): void
    {
        $event = new PreExportEvent(
            entityClass: User::class,
            format: ExportFormat::CSV,
            criteria: ['status' => 'active'],
            limit: 100,
            offset: 10,
            orderBy: ['name' => 'ASC'],
            fields: ['id', 'name'],
            options: ['boolean_to_integer' => true]
        );

        self::assertSame(User::class, $event->getEntityClass());
        self::assertSame(ExportFormat::CSV, $event->getFormat());
        self::assertSame(['status' => 'active'], $event->getCriteria());
        self::assertSame(100, $event->getLimit());
        self::assertSame(10, $event->getOffset());
        self::assertSame(['name' => 'ASC'], $event->getOrderBy());
        self::assertSame(['id', 'name'], $event->getFields());
        self::assertSame(['boolean_to_integer' => true], $event->getOptions());
    }

    public function testPostExportEventGetters(): void
    {
        $event = new PostExportEvent(
            entityClass: User::class,
            format: ExportFormat::JSON,
            criteria: ['role' => 'admin'],
            limit: 50,
            offset: 5,
            orderBy: ['email' => 'DESC'],
            fields: ['id', 'email'],
            options: ['datetime_format' => 'Y-m-d'],
            exportedCount: 42,
            durationInSeconds: 1.234
        );

        self::assertSame(User::class, $event->getEntityClass());
        self::assertSame(ExportFormat::JSON, $event->getFormat());
        self::assertSame(['role' => 'admin'], $event->getCriteria());
        self::assertSame(50, $event->getLimit());
        self::assertSame(5, $event->getOffset());
        self::assertSame(['email' => 'DESC'], $event->getOrderBy());
        self::assertSame(['id', 'email'], $event->getFields());
        self::assertSame(['datetime_format' => 'Y-m-d'], $event->getOptions());
        self::assertSame(42, $event->getExportedCount());
        self::assertSame(1.234, $event->getDurationInSeconds());
    }

    public function testPreExportEventWithNullValues(): void
    {
        $event = new PreExportEvent(
            entityClass: User::class,
            format: ExportFormat::XML,
            criteria: [],
            limit: null,
            offset: null,
            orderBy: [],
            fields: [],
            options: []
        );

        self::assertSame(User::class, $event->getEntityClass());
        self::assertSame(ExportFormat::XML, $event->getFormat());
        self::assertSame([], $event->getCriteria());
        self::assertNull($event->getLimit());
        self::assertNull($event->getOffset());
        self::assertSame([], $event->getOrderBy());
        self::assertSame([], $event->getFields());
        self::assertSame([], $event->getOptions());
    }

    public function testPostExportEventWithZeroCount(): void
    {
        $event = new PostExportEvent(
            entityClass: User::class,
            format: ExportFormat::CSV,
            criteria: [],
            limit: null,
            offset: null,
            orderBy: [],
            fields: [],
            options: [],
            exportedCount: 0,
            durationInSeconds: 0.001
        );

        self::assertSame(0, $event->getExportedCount());
        self::assertSame(0.001, $event->getDurationInSeconds());
    }
}
