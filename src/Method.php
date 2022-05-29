<?php

namespace Hyqo\Http;

use Hyqo\Enum\Enum;

/**
 * @method static HEAD
 * @method static OPTIONS
 * @method static TRACE
 * @method static GET
 * @method static POST
 * @method static PUT
 * @method static DELETE
 * @method static PATCH
 */
class Method extends Enum
{
    public const HEAD = 'HEAD';
    public const OPTIONS = 'OPTIONS';
    public const TRACE = 'TRACE';

    public const GET = 'GET';

    public const POST = 'POST';
    public const PUT = 'PUT';
    public const DELETE = 'DELETE';
    public const PATCH = 'PATCH';

    public function isSafe(): bool
    {
        return in_array($this->value, [self::HEAD, self::GET, self::OPTIONS, self::TRACE], true);
    }

    public function isIdempotent(): bool
    {
        return in_array($this->value, [self::HEAD, self::GET, self::PUT, self::DELETE, self::OPTIONS], true);
    }

    public function isCacheable(): bool
    {
        return in_array($this->value, [self::HEAD, self::GET], true);
    }
}
