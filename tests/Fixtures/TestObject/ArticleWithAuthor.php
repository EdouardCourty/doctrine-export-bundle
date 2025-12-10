<?php

declare(strict_types=1);

namespace Ecourty\DoctrineExportBundle\Tests\Fixtures\TestObject;

class ArticleWithAuthor
{
    public function __construct(private object $author, private string $title = 'Test Article')
    {
    }

    public function getAuthor(): object
    {
        return $this->author;
    }

    public function getTitle(): string
    {
        return $this->title;
    }
}
