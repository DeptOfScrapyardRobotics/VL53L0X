<?php

namespace DeptOfScrapyardRobotics\Sensors\VL53Lxx;

use GeneralPurposeIO\Contracts\Circuits\CircuitException;

class VL53LxxException extends CircuitException
{
    public static function transportMissingProtocol(): static
    {
        return new static('VL53Lxx devices require an I2C capable connection.');
    }

    public static function i2cTransactionFailed(): static
    {
        return new static('VL53Lxx I2C writeRead transaction failed.');
    }
}
