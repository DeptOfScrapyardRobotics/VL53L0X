<?php

namespace DeptOfScrapyardRobotics\Sensors\VL53Lxx\Concerns;

use DeptOfScrapyardRobotics\Sensors\VL53Lxx\VL53LxxException;

trait VL53LxxIO
{
    /**
     * @param  array<int, int>  $data
     *
     * @throws VL53LxxException
     */
    protected function i2cWrite(array $data): int
    {
        if (! is_null($this->i2c)) {
            return $this->i2c->write($data);
        }

        throw VL53LxxException::transportMissingProtocol();
    }

    /**
     * @param  array<int, int>  $bytes_to_write
     * @return array<int, int>
     *
     * @throws VL53LxxException
     */
    protected function i2cWriteRead(array $bytes_to_write, int $length): array
    {
        if (! is_null($this->i2c)) {
            $data = $this->i2c->writeRead($bytes_to_write, $length);

            if ($data === false) {
                throw VL53LxxException::i2cTransactionFailed();
            }

            return array_values($data);
        }

        throw VL53LxxException::transportMissingProtocol();
    }
}
