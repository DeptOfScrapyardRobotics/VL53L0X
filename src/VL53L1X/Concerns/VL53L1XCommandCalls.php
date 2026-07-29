<?php

namespace DeptOfScrapyardRobotics\Sensors\VL53Lxx\VL53L1X\Concerns;

use DeptOfScrapyardRobotics\Sensors\VL53Lxx\VL53L1X\Enums\VL53L1XRegister;
use DeptOfScrapyardRobotics\Sensors\VL53Lxx\VL53L1X\Exceptions\VL53L1XException;

trait VL53L1XCommandCalls
{
    /**
     * ST software reset (SOFT_RESET @ 0x0000). Required after XSHUT/power-on
     * before FIRMWARE__SYSTEM_STATUS will report boot complete.
     */
    public function softReset(): void
    {
        $this->sendCommand([VL53L1XRegister::SOFT_RESET->value, 0x00]);
        usleep(100);
        $this->sendCommand([VL53L1XRegister::SOFT_RESET->value, 0x01]);
        // Chip NACKs until the boot ROM is back; give it a beat before polling.
        usleep(1_000);
    }

    /**
     * Polls FIRMWARE__SYSTEM_STATUS until the device reports it has finished
     * its power-on boot (bit 0 set).
     *
     * @throws VL53L1XException
     */
    public function awaitBootComplete(int $timeoutMs = 2000): void
    {
        $startedAt = microtime(true);
        $sawReadableStatus = false;

        do {
            $status = $this->readByteOrNull(VL53L1XRegister::FIRMWARE_SYSTEM_STATUS->value);

            if (! is_null($status)) {
                $sawReadableStatus = true;

                if (($status & 0x01) === 0x01) {
                    return;
                }
            }

            usleep(1_000);
        } while (((microtime(true) - $startedAt) * 1000.0) < $timeoutMs);

        if (! $sawReadableStatus) {
            throw VL53L1XException::i2cReadFailed(
                VL53L1XRegister::FIRMWARE_SYSTEM_STATUS->value,
                1,
            );
        }

        throw VL53L1XException::bootTimeout();
    }

    /**
     * @throws VL53L1XException
     */
    public function readDeviceId(): int
    {
        [$high, $low] = $this->readData(VL53L1XRegister::IDENTIFICATION_MODEL_ID->value, 2);

        return ($high << 8) | $low;
    }

    public function startRanging(): void
    {
        $this->sendCommand([VL53L1XRegister::SYSTEM_MODE_START->value, 0x40]);
    }

    public function stopRanging(): void
    {
        // ST ULD StopRanging writes 0x00 (not Pololu's 0x80 abort).
        $this->sendCommand([VL53L1XRegister::SYSTEM_MODE_START->value, 0x00]);
    }

    public function clearInterrupt(): void
    {
        $this->sendCommand([VL53L1XRegister::SYSTEM_INTERRUPT_CLEAR->value, 0x01]);
    }

    /**
     * ST ULD VL53L1X_CheckForDataReady: compare GPIO__TIO_HV_STATUS bit 0 to
     * the polarity from GPIO_HV_MUX__CTRL bit 4
     * (bit4 clear => active-high / IntPol=1; bit4 set => active-low / IntPol=0).
     */
    public function statusReady(): bool
    {
        $mux = $this->readByteOrNull(VL53L1XRegister::GPIO_HV_MUX_CTRL->value);
        $status = $this->readByteOrNull(VL53L1XRegister::GPIO_TIO_HV_STATUS->value);

        if (is_null($mux) || is_null($status)) {
            return false;
        }

        $interruptPolarity = (($mux & 0x10) === 0) ? 0x01 : 0x00;

        return ($status & 0x01) === $interruptPolarity;
    }

    /**
     * Reads the final crosstalk-corrected range as a 16-bit value in mm.
     *
     * @throws VL53L1XException
     */
    public function readStatus(): int
    {
        [$high, $low] = $this->readData(VL53L1XRegister::RESULT_FINAL_CROSSTALK_CORRECTED_RANGE_MM_SD0->value, 2);

        return ($high << 8) | $low;
    }

    /**
     * Two-bound VHV plus starting VHV from the previous temperature, applied
     * once after the first throwaway range during boot.
     */
    protected function primeVhvConfig(): void
    {
        $this->sendCommand([VL53L1XRegister::VHV_CONFIG_TIMEOUT_MACROP_LOOP_BOUND->value, 0x09]);
        $this->sendCommand([VL53L1XRegister::VHV_CONFIG_INIT->value, 0x00]);
    }

    /**
     * Writes ST's canonical default configuration block (AN ULD), one byte per
     * register from CONFIG_START (0x2D) through SYSTEM__MODE_START (0x87).
     */
    protected function loadDefaultConfiguration(): void
    {
        $base = VL53L1XRegister::CONFIG_START->value;

        // Exact ST VL51L1X_DEFAULT_CONFIGURATION (stm32duino / STSW-IMG009 ULD).
        // 0x30 = 0x01 => active-high interrupt (bit4 clear); do not "fix" to 0x11.
        $configuration = [
            0x00, 0x00, 0x00, 0x01, 0x02, 0x00, 0x02, 0x08, // 0x2D - 0x34
            0x00, 0x08, 0x10, 0x01, 0x01, 0x00, 0x00, 0x00, // 0x35 - 0x3C
            0x00, 0xFF, 0x00, 0x0F, 0x00, 0x00, 0x00, 0x00, // 0x3D - 0x44
            0x00, 0x20, 0x0B, 0x00, 0x00, 0x02, 0x0A, 0x21, // 0x45 - 0x4C
            0x00, 0x00, 0x05, 0x00, 0x00, 0x00, 0x00, 0xC8, // 0x4D - 0x54
            0x00, 0x00, 0x38, 0xFF, 0x01, 0x00, 0x08, 0x00, // 0x55 - 0x5C
            0x00, 0x01, 0xCC, 0x0F, 0x01, 0xF1, 0x0D, 0x01, // 0x5D - 0x64
            0x68, 0x00, 0x80, 0x08, 0xB8, 0x00, 0x00, 0x00, // 0x65 - 0x6C
            0x00, 0x0F, 0x89, 0x00, 0x00, 0x00, 0x00, 0x00, // 0x6D - 0x74
            0x00, 0x00, 0x01, 0x0F, 0x0D, 0x0E, 0x0E, 0x00, // 0x75 - 0x7C
            0x00, 0x02, 0xC7, 0xFF, 0x9B, 0x00, 0x00, 0x00, // 0x7D - 0x84
            0x01, 0x00, 0x00,                               // 0x85 - 0x87
        ];

        foreach ($configuration as $offset => $value) {
            $this->sendCommand([$base + $offset, $value]);
        }
    }

    /**
     * Reads a single register, returning null instead of throwing when the bus
     * read fails - used only while polling for boot/measurement readiness where
     * a transient NAK simply means "not ready yet".
     */
    private function readByteOrNull(int $register): ?int
    {
        try {
            // Single attempt while polling — NAKs during boot are expected.
            [$value] = $this->readData($register, 1, 1);

            return $value;
        } catch (VL53L1XException) {
            return null;
        }
    }
}
