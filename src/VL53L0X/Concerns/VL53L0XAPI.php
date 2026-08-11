<?php

namespace DeptOfScrapyardRobotics\Sensors\VL53Lxx\VL53L0X\Concerns;

use DeptOfScrapyardRobotics\Sensors\VL53Lxx\VL53L0X\Enums\VL53L0XOpCode;
use DeptOfScrapyardRobotics\Sensors\VL53Lxx\VL53L0X\Enums\VL53L0XP1CommandRegister;
use DeptOfScrapyardRobotics\Sensors\VL53Lxx\VL53L0X\Exceptions\VL53L0XException;
use GeneralPurposeIO\Contracts\Circuits\BootScaffolding;

trait VL53L0XAPI
{
    use BootScaffolding, VL53L0XCommandCalls;

    protected ?int $stop_var = null;

    protected int $hardwired_device_id = 0xEE;

    protected int $hardwired_revision_id = 0x10;

    protected ?int $ref_spad_type = null;

    protected ?int $ref_spad_count = null;

    protected ?int $osc_freq = null;

    protected array $nvm_ref_good_spad_map = [];

    protected ?int $vhv_settings = null;

    protected ?int $phase_cal = null;

    /**
     * @throws VL53L0XException
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
     * @throws VL53L0XException
     */
    protected function reset(): void
    {
        if (is_null($this->xshut)) {
            return;
        }

        $this->xshut->low();
        usleep(10_000);

        if (! $this->xshut->high()) {
            throw VL53L0XException::xshutDriveFailed();
        }

        usleep(10_000);
    }

    /**
     * @throws VL53L0XException
     */
    protected function dataInit(): void
    {
        // XSHUT (if any) was already pulsed in _boot()/i2c() before this I2C work.
        $this->setI2CVoltageMode();

        $device_id = $this->readDeviceId();
        if ($device_id != $this->hardwired_device_id) {
            throw VL53L0XException::invalidModelId($device_id);
        }

        $revision_id = $this->readRevisionId();
        if ($revision_id != $this->hardwired_revision_id) {
            throw VL53L0XException::invalidRevisionId($revision_id);
        }

        $this->powerOn();
        $this->setSequenceConfig();
        $this->setLimitCheckValue(0, 18 * 65536);
        $this->setLimitCheckValue(1, (int) ((25 * 65536) / 100));
        $this->setLimitCheckValue(2, 35 * 65536);
        $this->setLimitCheckValue(3, 0);
    }

    /**
     * @throws VL53L0XException
     */
    protected function staticInit(): void
    {
        $R = VL53L0XOpCode::class;
        $P = VL53L0XP1CommandRegister::class;

        $this->readNVMInfo();
        $this->loadTuningSettings();

        $this->sendCommand([$R::SYSTEM_INTERRUPT_CONFIG_GPIO->value, 0x04]);

        // Read oscillator frequency from page 1
        $this->sendCommand([$R::PAGE_SELECT->value, 0x01]);
        [$oscLow, $oscHigh] = $this->readData($P::OSC_FREQUENCY->value, 2);
        $this->sendCommand([$R::PAGE_SELECT->value, 0x00]);
        $this->osc_freq = ($oscHigh << 8) | $oscLow;

        // Disable TCC (bit 4) and MSRC (bit 2) in sequence config
        $seqConfig = 0xFF & ~0x10 & ~0x04;
        $this->sendCommand([$R::SYSTEM_SEQUENCE_CONFIG->value, $seqConfig]);
    }

    protected function sendCommand(array $bytes): void
    {
        $register = array_shift($bytes);
        $this->write($register, $bytes);
    }

    /**
     * VL53L0X uses 8-bit register addresses.
     *
     * @throws VL53L0XException
     */
    protected function readData(int $register, int $length, int $attempts = 5): array
    {
        for ($attempt = 1; $attempt <= $attempts; $attempt++) {
            try {
                return $this->transport->writeRead([($register & 0xFF)], $length);
            } catch (\Throwable) {
                usleep(500);
            }
        }

        throw VL53L0XException::i2cReadFailed($register, $length);
    }

    /**
     * @throws VL53L0XException
     */
    protected function _boot(): void
    {
        $this->reset();
        $this->dataInit();
        $this->staticInit();
        $this->performRefCalibration();
        $this->performRefSpadManagement();
        $this->setDeviceMode();
    }

    protected function write(int $register_hex, array $command_data = []): int
    {
        return $this->transport->write([($register_hex & 0xFF), ...$command_data]);
    }

    protected function sequenceConfig(): int
    {
        $register = 0;
        if ($this->final_range_enabled) {
            $register |= (1 << 7);
        }
        if ($this->pre_range_enabled) {
            $register |= (1 << 6);
        }
        if ($this->tcc_enabled) {
            $register |= (1 << 4);
        }
        if ($this->dss_enabled) {
            $register |= (1 << 3);
        }
        if ($this->msrc_enabled) {
            $register |= (1 << 2);
        }

        return $register;
    }
}
