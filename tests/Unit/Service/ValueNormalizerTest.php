<?php

declare(strict_types=1);

namespace Ecourty\DoctrineExportBundle\Tests\Unit\Service;

use Ecourty\DoctrineExportBundle\Contract\DoctrineExporterInterface;
use Ecourty\DoctrineExportBundle\Service\ExportOptionsResolver;
use Ecourty\DoctrineExportBundle\Service\ValueNormalizer;
use Ecourty\DoctrineExportBundle\Tests\Fixtures\TestObject\ObjectWithoutToString;
use Ecourty\DoctrineExportBundle\Tests\Fixtures\TestObject\ObjectWithToString;
use PHPUnit\Framework\TestCase;

class ValueNormalizerTest extends TestCase
{
    private ValueNormalizer $normalizer;

    protected function setUp(): void
    {
        $this->normalizer = new ValueNormalizer(new ExportOptionsResolver());
    }

    public function testNormalizeDateTimeWithDefaultFormat(): void
    {
        $dateTime = new \DateTime('2024-01-15 10:30:00');
        $result = $this->normalizer->normalize($dateTime, []);

        $this->assertSame($dateTime->format(\DateTimeInterface::ATOM), $result);
    }

    public function testNormalizeDateTimeWithCustomFormat(): void
    {
        $dateTime = new \DateTime('2024-01-15 10:30:00');
        $result = $this->normalizer->normalize($dateTime, [
            DoctrineExporterInterface::OPTION_DATETIME_FORMAT => 'Y-m-d H:i:s',
        ]);

        $this->assertSame('2024-01-15 10:30:00', $result);
    }

    public function testNormalizeDateTimeImmutable(): void
    {
        $dateTime = new \DateTimeImmutable('2024-06-20 15:45:30');
        $result = $this->normalizer->normalize($dateTime, [
            DoctrineExporterInterface::OPTION_DATETIME_FORMAT => 'Y-m-d',
        ]);

        $this->assertSame('2024-06-20', $result);
    }

    public function testNormalizeBooleanToIntegerByDefault(): void
    {
        $this->assertSame(1, $this->normalizer->normalize(true, []));
        $this->assertSame(0, $this->normalizer->normalize(false, []));
    }

    public function testNormalizeBooleanToString(): void
    {
        $options = [DoctrineExporterInterface::OPTION_BOOLEAN_TO_INTEGER => false];

        $this->assertSame('true', $this->normalizer->normalize(true, $options));
        $this->assertSame('false', $this->normalizer->normalize(false, $options));
    }

    public function testNormalizeNullReturnsNullByDefault(): void
    {
        $result = $this->normalizer->normalize(null, []);

        $this->assertNull($result);
    }

    public function testNormalizeNullWithCustomStringValue(): void
    {
        $result = $this->normalizer->normalize(null, [
            DoctrineExporterInterface::OPTION_NULL_VALUE => 'N/A',
        ]);

        $this->assertSame('N/A', $result);
    }

    public function testNormalizeNullWithCustomIntValue(): void
    {
        $result = $this->normalizer->normalize(null, [
            DoctrineExporterInterface::OPTION_NULL_VALUE => 0,
        ]);

        $this->assertSame(0, $result);
    }

    public function testNormalizeNullWithEmptyStringValue(): void
    {
        $result = $this->normalizer->normalize(null, [
            DoctrineExporterInterface::OPTION_NULL_VALUE => '',
        ]);

        $this->assertSame('', $result);
    }

    public function testNormalizeNullWithFloatValue(): void
    {
        $result = $this->normalizer->normalize(null, [
            DoctrineExporterInterface::OPTION_NULL_VALUE => 0.0,
        ]);

        $this->assertSame(0.0, $result);
    }

    public function testNormalizeArrayToJson(): void
    {
        $array = ['name' => 'John', 'age' => 30, 'active' => true];
        $result = $this->normalizer->normalize($array, []);

        $this->assertSame('{"name":"John","age":30,"active":true}', $result);
    }

    public function testNormalizeEmptyArrayToJson(): void
    {
        $result = $this->normalizer->normalize([], []);

        $this->assertSame('[]', $result);
    }

    public function testNormalizeNestedArrayToJson(): void
    {
        $array = ['user' => ['name' => 'Jane', 'roles' => ['admin', 'user']]];
        $result = $this->normalizer->normalize($array, []);

        $this->assertSame('{"user":{"name":"Jane","roles":["admin","user"]}}', $result);
    }

    public function testNormalizeObjectWithToString(): void
    {
        $object = new ObjectWithToString();

        $result = $this->normalizer->normalize($object, []);

        $this->assertSame('CustomObject', $result);
    }

    public function testNormalizeObjectWithoutToStringReturnsClassName(): void
    {
        $object = new \stdClass();
        $result = $this->normalizer->normalize($object, []);

        $this->assertSame('stdClass', $result);
    }

    public function testNormalizeObjectWithNamespace(): void
    {
        $object = new ObjectWithoutToString();

        $result = $this->normalizer->normalize($object, []);

        $this->assertIsString($result);
        $this->assertSame(ObjectWithoutToString::class, $result);
    }

    public function testNormalizeStringValue(): void
    {
        $this->assertSame('hello', $this->normalizer->normalize('hello', []));
        $this->assertSame('', $this->normalizer->normalize('', []));
        $this->assertSame('123', $this->normalizer->normalize('123', []));
    }

    public function testNormalizeIntegerValue(): void
    {
        $this->assertSame(42, $this->normalizer->normalize(42, []));
        $this->assertSame(0, $this->normalizer->normalize(0, []));
        $this->assertSame(-100, $this->normalizer->normalize(-100, []));
    }

    public function testNormalizeFloatValue(): void
    {
        $this->assertSame(3.14, $this->normalizer->normalize(3.14, []));
        $this->assertSame(0.0, $this->normalizer->normalize(0.0, []));
        $this->assertSame(-99.99, $this->normalizer->normalize(-99.99, []));
    }

    public function testNormalizeResourceReturnsNull(): void
    {
        $resource = fopen('php://memory', 'r');
        $this->assertNotFalse($resource);
        $result = $this->normalizer->normalize($resource, []);
        fclose($resource);

        $this->assertNull($result);
    }

    public function testNormalizeHandlesSpecialFloatValues(): void
    {
        $this->assertSame(INF, $this->normalizer->normalize(INF, []));
        $this->assertSame(-INF, $this->normalizer->normalize(-INF, []));
        $nanResult = $this->normalizer->normalize(NAN, []);
        $this->assertIsFloat($nanResult);
        $this->assertTrue(is_nan($nanResult));
    }

    public function testNormalizePreservesNumericStringTypes(): void
    {
        // String numbers should remain strings
        $this->assertSame('42', $this->normalizer->normalize('42', []));
        $this->assertSame('3.14', $this->normalizer->normalize('3.14', []));
        $this->assertSame('0', $this->normalizer->normalize('0', []));
    }

    public function testNormalizeWithMultipleOptionsDoesNotInterfere(): void
    {
        $options = [
            DoctrineExporterInterface::OPTION_BOOLEAN_TO_INTEGER => false,
            DoctrineExporterInterface::OPTION_DATETIME_FORMAT => 'Y-m-d',
            DoctrineExporterInterface::OPTION_NULL_VALUE => 'NULL',
        ];

        // Each type should use its corresponding option
        $this->assertSame('true', $this->normalizer->normalize(true, $options));
        $this->assertSame('NULL', $this->normalizer->normalize(null, $options));
        $this->assertSame('2024-01-01', $this->normalizer->normalize(new \DateTime('2024-01-01'), $options));
    }
}
