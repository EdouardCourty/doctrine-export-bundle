<?php

declare(strict_types=1);

namespace Ecourty\DoctrineExportBundle\Tests\Enum;

use Ecourty\DoctrineExportBundle\Enum\ExportFormat;
use PHPUnit\Framework\TestCase;

class ExportFormatTest extends TestCase
{
    public function testFromString(): void
    {
        $this->assertSame(ExportFormat::CSV, ExportFormat::fromString('csv'));
        $this->assertSame(ExportFormat::JSON, ExportFormat::fromString('json'));
        $this->assertSame(ExportFormat::XML, ExportFormat::fromString('xml'));

        $this->assertSame(ExportFormat::CSV, ExportFormat::fromString('CSV'));
        $this->assertSame(ExportFormat::JSON, ExportFormat::fromString('JSON'));
        $this->assertSame(ExportFormat::XML, ExportFormat::fromString('XML'));
    }

    public function testFromStringWithInvalidFormat(): void
    {
        $this->expectException(\ValueError::class);
        ExportFormat::fromString('invalid');
    }
}
