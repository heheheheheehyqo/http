<?php

namespace Hyqo\Http\Pool;

class AttributePool extends Pool
{
    public function getInt(string $key, int $default = 0): int
    {
        return (int)$this->get($key, $default);
    }

    public function getBoolean(string $key, bool $default = false): bool
    {
        return $this->filter($key, $default, \FILTER_VALIDATE_BOOLEAN);
    }

}
