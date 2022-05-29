<?php

namespace Hyqo\Http\Pool;

abstract class Pool implements \IteratorAggregate, \Countable
{
    /** @var array */
    protected $parameters;

    public function __construct(array $parameters = [])
    {
        $this->parameters = $parameters;
    }

    public function all(): array
    {
        return $this->parameters;
    }

    public function keys(): array
    {
        return array_keys($this->parameters);
    }

    public function replace(array $parameters = []): void
    {
        $this->parameters = $parameters;
    }

    public function add(array $parameters = []): void
    {
        $this->parameters = array_replace($this->parameters, $parameters);
    }

    public function get(string $key, $default = null)
    {
        return $this->has($key) ? $this->parameters[$key] : $default;
    }

    public function has(string $key): bool
    {
        return \array_key_exists($key, $this->parameters);
    }

    public function set(string $key, $value): void
    {
        $this->parameters[$key] = $value;
    }

    public function remove(string $key): void
    {
        unset($this->parameters[$key]);
    }

    /**
     * @param array|int|string|\Closure $options
     */
    public function filter(string $key, $default = null, int $filter = \FILTER_DEFAULT, $options = [])
    {
        $value = $this->get($key, $default);

        if (!\is_array($options) && $options) {
            $options = ['flags' => $options];
        }

        if (\is_array($value) && !isset($options['flags'])) {
            $options['flags'] = \FILTER_REQUIRE_ARRAY;
        }

        if (\FILTER_CALLBACK & $filter) {
            $callable = ($options['options'] ?? null);

            if (is_string($callable) && !function_exists($callable)) {
                throw new \InvalidArgumentException(
                    sprintf(
                        'The function named "%s" passed to "%s()" does not exists',
                        $callable,
                        __METHOD__
                    )
                );
            }

            if (!is_string($callable) && !($callable instanceof \Closure)) {
                throw new \InvalidArgumentException(
                    sprintf(
                        'A Closure must be passed to "%s()" when FILTER_CALLBACK is used, "%s" given.',
                        __METHOD__,
                        gettype($callable)
                    )
                );
            }
        }

        return filter_var($value, $filter, $options);
    }

    /**
     * Returns an iterator for parameters.
     *
     * @return \ArrayIterator<string, mixed>
     */
    public function getIterator(): \ArrayIterator
    {
        return new \ArrayIterator($this->parameters);
    }

    public function count(): int
    {
        return \count($this->parameters);
    }
}
