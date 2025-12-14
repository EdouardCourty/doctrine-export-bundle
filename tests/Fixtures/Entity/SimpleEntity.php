<?php

declare(strict_types=1);

namespace Ecourty\DoctrineExportBundle\Tests\Fixtures\Entity;

class SimpleEntity
{
    public int $id;
    public string $name;

    public function __construct(int $id, string $name)
    {
        $this->id = $id;
        $this->name = $name;
    }
}
