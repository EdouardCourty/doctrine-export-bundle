<?php

declare(strict_types=1);

namespace Ecourty\DoctrineExportBundle\Tests\Fixtures\TestObject;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;

class ArticleWithTags
{
    /** @var Collection<int, TagWithId> */
    private Collection $tags;

    /**
     * @param array<TagWithId> $tags
     */
    public function __construct(array $tags, private string $title = 'Test Article')
    {
        $this->tags = new ArrayCollection($tags);
    }

    /**
     * @return Collection<int, TagWithId>
     */
    public function getTags(): Collection
    {
        return $this->tags;
    }

    public function getTitle(): string
    {
        return $this->title;
    }
}
