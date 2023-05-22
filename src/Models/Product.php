<?php

namespace Microit\StoreAhApi\Models;

class Product
{
    public function __construct(
        readonly int $id,
        readonly string $title,
        readonly ?string $brand
    )
    {
    }
}
