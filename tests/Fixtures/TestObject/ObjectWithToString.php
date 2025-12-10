<?php

declare(strict_types=1);

namespace Ecourty\DoctrineExportBundle\Tests\Fixtures\TestObject;

class ObjectWithToString
{
    public function __construct(private string $value = 'CustomObject')
    {
    }

    public function __toString(): string
    {
        return $this->value;
    }
}
