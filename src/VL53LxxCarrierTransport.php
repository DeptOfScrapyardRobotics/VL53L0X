<?php

namespace DeptOfScrapyardRobotics\Sensors\VL53Lxx;

use DeptOfScrapyardRobotics\Sensors\VL53Lxx\Concerns\VL53LxxIO;
use GeneralPurposeIO\I2C\I2CSlave;

class VL53LxxCarrierTransport
{
    use VL53LxxIO;

    public readonly string $active_transport;

    /**
     * @throws VL53LxxException
     */
    public function __construct(
        protected ?I2CSlave $i2c = null,
    ) {
        $this->active_transport = $this->detectTransport();
    }

    /**
     * @param  array<int, int>  $data
     *
     * @throws VL53LxxException
     */
    public function write(array $data): int
    {
        $method = "{$this->active_transport}Write";

        return $this->{$method}($data);
    }

    /**
     * @param  array<int, int>  $bytes_to_write
     * @return array<int, int>
     *
     * @throws VL53LxxException
     */
    public function writeRead(array $bytes_to_write, int $length): array
    {
        $method = "{$this->active_transport}WriteRead";

        return $this->{$method}($bytes_to_write, $length);
    }

    /**
     * @throws VL53LxxException
     */
    protected function detectTransport(): string
    {
        if (! is_null($this->i2c)) {
            return 'i2c';
        }

        throw VL53LxxException::transportMissingProtocol();
    }

    public function close(): void
    {
        $this->i2c?->close();
    }
}
