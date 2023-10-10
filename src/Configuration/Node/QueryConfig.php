<?php

declare(strict_types=1);

namespace Keboola\FacebookExtractor\Configuration\Node;

class QueryConfig
{
    public function __construct(
        readonly private ?string $path,
        readonly private ?string $fields,
        readonly private ?string $parameters,
        readonly private ?string $ids,
        readonly private ?string $since,
        readonly private ?string $until,
    ) {
    }

    public static function fromArray(array $query): self
    {
        return new self(
            $query['path'] ?? null,
            $query['fields'] ?? null,
            $query['parameters'] ?? null,
            $query['ids'] ?? null,
            $query['since'] ?? null,
            $query['until'] ?? null,
        );
    }

    public function hasPath(): bool
    {
        return !empty($this->path);
    }

    public function getPath(): ?string
    {
        return $this->path;
    }

    public function hasFields(): bool
    {
        return !empty($this->fields);
    }

    public function getFields(): ?string
    {
        return $this->fields;
    }

    public function getParameters(): ?string
    {
        return $this->parameters;
    }

    public function getIds(): ?string
    {
        return $this->ids;
    }

    public function getSince(): ?string
    {
        return $this->since;
    }

    public function getUntil(): ?string
    {
        return $this->until;
    }
}
