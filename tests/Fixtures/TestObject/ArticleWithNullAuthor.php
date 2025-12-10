<?php

declare(strict_types=1);

namespace Ecourty\DoctrineExportBundle\Tests\Fixtures\TestObject;

class ArticleWithNullAuthor
{
    public function __construct(private string $title = 'Test Article')
    {
    }

    public function getAuthor(): null
    {
        return null;
    }

    public function getTitle(): string
    {
        return $this->title;
    }
}
