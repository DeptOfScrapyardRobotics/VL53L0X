<?php

namespace ScrapyardIO\Sensors\Distance\VL53L0X\Exceptions;

use ScrapyardIO\Support\Exceptions\ScrapyardIOException;
use ScrapyardIO\Support\Exceptions\SensorIOException;

class VL53L0XException extends SensorIOException
{
    public static function invalidProtocol(string $name): static
    {
        return new static("Unsupported protocol '{$name}'.");
    }

    public static function invalidDeviceID(int|string $id, int $expected): static
    {
        return new static("Invalid Device ID '{$id}'. Expected '{$expected}'.");
    }

    public static function invalidRevisionID(int|string $id, int $expected): static
    {
        return new static("Invalid Revision ID '{$id}'. Expected '{$expected}'.");
    }
    
    public static function strobeTimeout(): static
    {
        return new static("Device read strobe timeout - NVM read operation failed.");
    }
}
