<?php

declare(strict_types=1);

namespace App\Resource\Admin;

final readonly class ResourceCollection
{
    /** @param list<object> $items */
    public function __construct(public array $items) {}
}
