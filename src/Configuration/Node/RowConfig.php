<?php

declare(strict_types=1);

namespace Keboola\FacebookExtractor\Configuration\Node;

class RowConfig
{
    public function __construct(
        readonly private string $name,
        readonly private string $type,
        readonly private QueryConfig $query,
    ) {
    }

    public static function fromArray(array $query): self
    {
        return new self($query['name'], $query['type'], QueryConfig::fromArray($query['query']));
    }

    public function getName(): string
    {
        return $this->name;
    }

    public function getType(): string
    {
        return $this->type;
    }

    public function getQuery(): QueryConfig
    {
        return $this->query;
    }
}
