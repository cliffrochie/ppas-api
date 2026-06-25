<?php

declare(strict_types=1);

namespace App\Exceptions;

final class InvalidStatusTransitionException extends \RuntimeException
{
    public function __construct(string $from, string $to)
    {
        parent::__construct("Invalid status transition from '{$from}' to '{$to}'.");
    }
}
