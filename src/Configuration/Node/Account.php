<?php

declare(strict_types=1);

namespace Keboola\FacebookExtractor\Configuration\Node;

class Account
{

    public function __construct(
        readonly string $id,
        readonly string $name,
        readonly string $category,
        readonly array $categoryList,
        readonly array $tasks,
    ) {
    }

    public static function fromArray(array $data): self
    {
        return new self(
            $data['id'],
            $data['name'],
            $data['category'] ?? '',
            $data['category_list'] ?? [],
            $data['tasks'] ?? [],
        );
    }

    public function getId(): string
    {
        return $this->id;
    }

    public function getName(): string
    {
        return $this->name;
    }

    public function getCategory(): string
    {
        return $this->category;
    }

    public function getCategoryList(): array
    {
        return $this->categoryList;
    }

    public function getTasks(): array
    {
        return $this->tasks;
    }

    public function toArray(): array
    {
        return [
            'id' => $this->getId(),
            'name' => $this->getName(),
            'category' => $this->getCategory(),
            'category_list' => json_encode($this->getCategoryList()),
            'tasks' => json_encode($this->getTasks()),
        ];
    }
}
