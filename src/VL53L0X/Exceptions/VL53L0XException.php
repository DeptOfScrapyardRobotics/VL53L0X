<?php

namespace DeptOfScrapyardRobotics\Sensors\VL53Lxx\VL53L0X\Exceptions;

use Fabricate\Contracts\Circuits\CircuitException;

class VL53L0XException extends CircuitException
{
    public static function invalidModelId(int $id): static
    {
        return new static(sprintf('Invalid VL53L0X model ID - 0x%02X (expected 0xEE)', $id));
    }

    public static function invalidRevisionId(int $id): static
    {
        return new static(sprintf('Invalid VL53L0X revision ID - 0x%02X (expected 0x10)', $id));
    }

    public static function strobeTimeout(): static
    {
        return new static('Device read strobe timeout - NVM read operation failed.');
    }

    public static function xshutPinNotConfigured(): static
    {
        return new static('Hardware reset requires a DigitalOutput XSHUT pin passed to the VL53L0X constructor.');
    }

    public static function digitalDeviceRequiredForXshut(): static
    {
        return new static('xshut_pin requires digital_device (and optional digital_adapter) when the I2C bus cannot serve digital pins.');
    }

    public static function xshutDriveFailed(): static
    {
        return new static('Failed to drive XSHUT high — sensor stays in hardware shutdown and will not ACK on I2C.');
    }

    public static function interruptPinNotConfigured(): static
    {
        return new static('Interrupt operations require a DigitalInput pin passed to the VL53L0X constructor.');
    }

    public static function i2cReadFailed(int $register, int $length): static
    {
        return new static(sprintf('I2C read failed - register 0x%02X, expected %d byte(s).', $register, $length));
    }

    public static function measurementTimeout(): static
    {
        return new static('Timed out waiting for the VL53L0X to report a completed measurement.');
    }
}
