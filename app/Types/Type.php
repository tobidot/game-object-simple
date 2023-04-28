<?php

namespace App\Types;

use App\Exceptions\TypeException;
use Exception;

class Type
{

    /**
     * @throws TypeException
     */
    public function __construct(array $input = [])
    {
        $final_class = get_called_class();
        $properties = get_class_vars($final_class);
        foreach ($properties as $name => $default) {
            $this->fillProperty($name, $input, $default);
        }
    }

    /**
     * @param string $name
     * @param array $input
     * @param mixed $default The fallback value if value is not given in the array
     * @return void
     * @throws TypeException
     */
    protected function fillProperty(
        string $name,
        array  $input,
        mixed  $default,
    ): void
    {
        if (array_key_exists($name, $input)) {
            $value = $this->maybeCastProperty($name, $input[$name]);
        } else {
            $value = $default;
        }
        try {
            $this->$name = $value;
        } catch (\TypeError $exception) {
            throw new TypeException(
                $class,
                $name,
                $value,
                $exception
            );
        }
    }

    /**
     * @throws TypeException
     */
    protected function maybeCastProperty(
        string $name,
        mixed  $value,
    ): mixed {
        if (!method_exists($this, $name)) {
            return $value;
        }
        try {
            return $this->$name($value);
        } catch (\ArgumentCountError|\TypeError $exception) {
            $final_class = get_called_class();
            throw new TypeException(
                $final_class,
                $name,
                $value,
                $exception
            );
        }
    }

    public function toArray() : array {
        return get_object_vars($this);
    }
}
