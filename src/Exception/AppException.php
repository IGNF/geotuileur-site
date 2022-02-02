<?php

namespace App\Exception;

use Exception;
use Throwable;

class AppException extends Exception
{
    private $details;

    public function __construct($message, $code = -1, $details = [], Throwable $previous = null)
    {
        $this->details = $details;
        parent::__construct($message, $code, $previous);
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
