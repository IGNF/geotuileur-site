<?php

namespace App\Exception;

use Symfony\Component\HttpKernel\Exception\HttpException;
use Throwable;

class AppException extends HttpException
{
    private $details;

    public function __construct($message, $statusCode = -1, $details = [], Throwable $previous = null)
    {
        $this->details = $details;
        $this->code = $statusCode;
        parent::__construct($statusCode, $message, $previous);
    }

    public function __toString()
    {
        return __CLASS__.": [{$this->code}]: {$this->message}\n";
    }

    public function getDetails()
    {
        return $this->details;
    }
}
