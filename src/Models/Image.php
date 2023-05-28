<?php

namespace Microit\StoreAhApi\Models;

class Image
{
    public function __construct(
        public readonly string $url,
        public readonly ?int $width,
        public readonly ?int $height
    ) {
    }
}
