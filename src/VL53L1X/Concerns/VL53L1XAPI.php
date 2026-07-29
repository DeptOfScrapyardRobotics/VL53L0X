<?php

namespace DeptOfScrapyardRobotics\Sensors\VL53Lxx\VL53L1X\Concerns;

use DeptOfScrapyardRobotics\Sensors\VL53Lxx\VL53L1X\Exceptions\VL53L1XException;
use Fabricate\Contracts\NutsAndBolts\BootScaffolding;

trait VL53L1XAPI
{
    use BootScaffolding, VL53L1XCommandCalls;

    protected int $hardwired_device_id = 0xEACC;

    /**
     * @throws VL53L1XException
     */
    public function statusWait(int $timeoutMs = 1000): bool
    {
        $startTime = microtime(true);
        while (! $this->statusReady()) {
            usleep(1_000);
            if ($timeoutMs > 0 && ((microtime(true) - $startTime) * 1000.0) >= $timeoutMs) {
                return false;
            }
        }

        return true;
    }

    /**
     * Hardware XSHUT pulse (active-low shutdown). Must run before any I2C.
     *
     * @throws VL53L1XException
     */
    protected function reset(): void
    {
        if (is_null($this->xshut)) {
            return;
        }

        // Claim leaves the line high; pulse low then release and verify.
        $this->xshut->low();
        usleep(10_000);

        if (! $this->xshut->high()) {
            throw VL53L1XException::xshutDriveFailed();
        }

        usleep(10_000);
    }

    /**
     * @throws VL53L1XException
     */
    protected function dataInit(): void
    {
        // Pololu/ST order: confirm the part, software-reset, then wait for FW.
        $device_id = $this->readDeviceId();
        if ($device_id !== $this->hardwired_device_id) {
            throw VL53L1XException::invalidModelId($device_id);
        }

        $this->softReset();
        $this->awaitBootComplete();
    }

    /**
     * ST ULD VL53L1X_SensorInit, then leave continuous ranging running.
     *
     * @throws VL53L1XException
     */
    protected function _boot(): void
    {
        $this->reset();
        $this->dataInit();
        $this->loadDefaultConfiguration();

        // Throwaway range + VHV prime (SensorInit). Fail hard if the first
        // sample never lands — continuing anyway leaves a dead ranging loop.
        $this->clearInterrupt();
        $this->startRanging();
        if (! $this->statusWait(5000)) {
            throw VL53L1XException::measurementTimeout();
        }
        $this->clearInterrupt();
        $this->stopRanging();
        $this->primeVhvConfig();

        $this->clearInterrupt();
        $this->startRanging();
    }

    protected function sendCommand(array $bytes): void
    {
        $register = array_shift($bytes);
        $this->write($register, $bytes);
    }

    /**
     * VL53L1X uses 16-bit register addresses sent MSB first.
     *
     * @throws VL53L1XException
     */
    protected function readData(int $register, int $length, int $attempts = 5): array
    {
        $address = [($register >> 8) & 0xFF, $register & 0xFF];

        for ($attempt = 1; $attempt <= $attempts; $attempt++) {
            try {
                return $this->transport->writeRead($address, $length);
            } catch (\Throwable) {
                usleep(500);
            }
        }

        throw VL53L1XException::i2cReadFailed($register, $length);
    }

    /**
     * VL53L1X uses 16-bit register addresses sent MSB first.
     */
    protected function write(int $register_hex, array $command_data = []): int
    {
        return $this->transport->write([
            ($register_hex >> 8) & 0xFF,
            $register_hex & 0xFF,
            ...$command_data,
        ]);
    }
}
