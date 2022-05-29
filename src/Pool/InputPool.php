<?php

namespace Hyqo\Http\Pool;

class InputPool extends Pool
{
    /**
     * @param string|int|float|bool|array|null $default
     * @return string|int|float|bool|array|null
     */
    public function get(string $key, $default = null)
    {
        if ($default !== null && !is_scalar($default) && !\is_array($default)) {
            throw new \InvalidArgumentException(
                sprintf(
                    'Excepted a scalar value as a 2nd argument to "%s()", "%s" given.',
                    __METHOD__,
                    gettype($default)
                )
            );
        }

        $value = parent::get($key, $default);

        if ($value !== null && !is_scalar($value) && !\is_array($value)) {
            throw new \UnexpectedValueException(sprintf('Input value "%s" contains a non-scalar value.', $key));
        }

        return $value;
    }

    /**
     * @param string|int|float|bool|array|null $value
     */
    public function set(string $key, $value): void
    {
        if ($value !== null && !is_scalar($value) && !\is_array($value)) {
            throw new \InvalidArgumentException(
                sprintf(
                    'Excepted a scalar, or an array as a 2nd argument to "%s()", "%s" given.',
                    __METHOD__,
                    gettype($value)
                )
            );
        }

        parent::set($key, $value);
    }
}
