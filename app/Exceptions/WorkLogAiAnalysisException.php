<?php

namespace App\Exceptions;

use RuntimeException;

class WorkLogAiAnalysisException extends RuntimeException
{
    public function __construct(string $message, public readonly int $statusCode)
    {
        parent::__construct($message);
    }
}
