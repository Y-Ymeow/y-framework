<?php

declare(strict_types=1);

namespace Framework\Cache\Exception;

class InvalidArgumentException extends \InvalidArgumentException implements \Psr\SimpleCache\InvalidArgumentException
{
}
