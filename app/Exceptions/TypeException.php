<?php

namespace App\Exceptions;

use Exception;

class TypeException extends Exception
{

    public string $value_type;

    public function __construct(
        public readonly string $type_class,
        public readonly string $property_name,
        public readonly mixed $value,
        \Throwable $previous,
    )
    {
        $this->value_type = gettype($this->value);
        if ($this->value_type === "object") {
            $this->value_type = get_class($this->value);
        }
        $message = "TypeException: Cannot assign value of type '$this->value_type' to '$this->type_class::$this->property_name'";
        parent::__construct($message, 0, $previous);
    }
}
