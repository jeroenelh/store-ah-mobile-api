<?php

namespace Microit\StoreAhApi\Models;

class Category
{
    /** @var Category[] */
    protected array $categories = [];

    public function __construct(
        public readonly int|string|null $id = null,
        public readonly ?string $name = null,
        public readonly ?string $slug = null,
        public readonly ?string $url = null,
        public readonly ?Image $image = null,
    ) {
    }

    public function getId(): int|string|null
    {
        return $this->id;
    }

    public function getName(): string|null
    {
        return $this->name;
    }

    public function getSlug(): string|null
    {
        return $this->slug;
    }

    public function getUrl(): string|null
    {
        return $this->url;
    }

    public function getImage(): Image|null
    {
        return $this->image;
    }

    public function addCategory(self $category): self
    {
        if (! $this->hasCategory($category)) {
            $this->categories[] = $category;
        }

        return $this;
    }

    public function hasCategory(self $category): bool
    {
        return in_array($category, $this->categories);
    }

    public function getCategories(): array
    {
        return $this->categories;
    }
}
