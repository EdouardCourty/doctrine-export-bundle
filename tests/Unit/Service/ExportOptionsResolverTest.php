<?php

declare(strict_types=1);

namespace Ecourty\DoctrineExportBundle\Tests\Unit\Service;

use Ecourty\DoctrineExportBundle\Contract\DoctrineExporterInterface;
use Ecourty\DoctrineExportBundle\Service\ExportOptionsResolver;
use PHPUnit\Framework\TestCase;

class ExportOptionsResolverTest extends TestCase
{
    private ExportOptionsResolver $resolver;

    protected function setUp(): void
    {
        $this->resolver = new ExportOptionsResolver();
    }

    public function testGetDateTimeFormatReturnsDefaultWhenNotSet(): void
    {
        $format = $this->resolver->getDateTimeFormat([]);

        $this->assertSame(\DateTimeInterface::ATOM, $format);
    }

    public function testGetDateTimeFormatReturnsCustomFormat(): void
    {
        $format = $this->resolver->getDateTimeFormat([
            DoctrineExporterInterface::OPTION_DATETIME_FORMAT => 'Y-m-d H:i:s',
        ]);

        $this->assertSame('Y-m-d H:i:s', $format);
    }

    public function testGetDateTimeFormatReturnsDefaultWhenInvalidType(): void
    {
        $format = $this->resolver->getDateTimeFormat([
            DoctrineExporterInterface::OPTION_DATETIME_FORMAT => 123,
        ]);

        $this->assertSame(\DateTimeInterface::ATOM, $format);
    }

    public function testGetNullValueReturnsNullByDefault(): void
    {
        $value = $this->resolver->getNullValue([]);

        $this->assertNull($value);
    }

    public function testGetNullValueReturnsCustomStringValue(): void
    {
        $value = $this->resolver->getNullValue([
            DoctrineExporterInterface::OPTION_NULL_VALUE => 'N/A',
        ]);

        $this->assertSame('N/A', $value);
    }

    public function testGetNullValueReturnsCustomIntValue(): void
    {
        $value = $this->resolver->getNullValue([
            DoctrineExporterInterface::OPTION_NULL_VALUE => 0,
        ]);

        $this->assertSame(0, $value);
    }

    public function testGetNullValueReturnsNullWhenBooleanProvided(): void
    {
        $value = $this->resolver->getNullValue([
            DoctrineExporterInterface::OPTION_NULL_VALUE => false,
        ]);

        $this->assertNull($value);
    }

    public function testGetNullValueReturnsNullWhenArrayProvided(): void
    {
        $value = $this->resolver->getNullValue([
            DoctrineExporterInterface::OPTION_NULL_VALUE => [],
        ]);

        $this->assertNull($value);
    }

    public function testShouldConvertBooleanToIntegerReturnsTrueByDefault(): void
    {
        $result = $this->resolver->shouldConvertBooleanToInteger([]);

        $this->assertTrue($result);
    }

    public function testShouldConvertBooleanToIntegerReturnsCustomValue(): void
    {
        $result = $this->resolver->shouldConvertBooleanToInteger([
            DoctrineExporterInterface::OPTION_BOOLEAN_TO_INTEGER => false,
        ]);

        $this->assertFalse($result);
    }

    public function testGetNullValueWithEmptyString(): void
    {
        $value = $this->resolver->getNullValue([
            DoctrineExporterInterface::OPTION_NULL_VALUE => '',
        ]);

        $this->assertSame('', $value);
    }

    public function testGetNullValueWithFloatZero(): void
    {
        $value = $this->resolver->getNullValue([
            DoctrineExporterInterface::OPTION_NULL_VALUE => 0.0,
        ]);

        $this->assertSame(0.0, $value);
    }

    public function testGetNullValueWithNegativeNumber(): void
    {
        $value = $this->resolver->getNullValue([
            DoctrineExporterInterface::OPTION_NULL_VALUE => -1,
        ]);

        $this->assertSame(-1, $value);
    }

    public function testGetNullValueDistinguishesZeroFromNull(): void
    {
        $valueWithZero = $this->resolver->getNullValue([
            DoctrineExporterInterface::OPTION_NULL_VALUE => 0,
        ]);
        $valueWithNull = $this->resolver->getNullValue([
            DoctrineExporterInterface::OPTION_NULL_VALUE => null,
        ]);

        $this->assertSame(0, $valueWithZero);
        $this->assertNull($valueWithNull);
    }

    public function testGetNullValueWithObjectReturnsNull(): void
    {
        $value = $this->resolver->getNullValue([
            DoctrineExporterInterface::OPTION_NULL_VALUE => new \stdClass(),
        ]);

        $this->assertNull($value);
    }
}
