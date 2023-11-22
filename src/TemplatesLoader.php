<?php

declare(strict_types=1);

namespace Keboola\FacebookExtractor;

use Exception;

class TemplatesLoader
{
    public function __construct(readonly private string $componentId)
    {
    }

    public function load(): array
    {
        $file = sprintf('%s/templates/%s.json', __DIR__, $this->componentId);
        if (!file_exists($file)) {
            return [];
        }

        return (array) json_decode(
            (string) file_get_contents($file),
            true,
        );
    }
}
