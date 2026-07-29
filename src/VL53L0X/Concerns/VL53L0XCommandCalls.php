<?php

namespace DeptOfScrapyardRobotics\Sensors\VL53Lxx\VL53L0X\Concerns;

use DeptOfScrapyardRobotics\Sensors\VL53Lxx\VL53L0X\Enums\VL53L0XCommandRegister;
use DeptOfScrapyardRobotics\Sensors\VL53Lxx\VL53L0X\Enums\VL53L0XOpCode;
use DeptOfScrapyardRobotics\Sensors\VL53Lxx\VL53L0X\Enums\VL53L0XP1CommandRegister;
use DeptOfScrapyardRobotics\Sensors\VL53Lxx\VL53L0X\Enums\VL53L0XP1TuningCommandRegister;
use DeptOfScrapyardRobotics\Sensors\VL53Lxx\VL53L0X\Enums\VL53L0XTuningCommandRegister;
use DeptOfScrapyardRobotics\Sensors\VL53Lxx\VL53L0X\Exceptions\VL53L0XException;

trait VL53L0XCommandCalls
{
    public function setI2CVoltageMode(): void
    {
        $value = $this->i2c_2v8_mode ? 0x01 : 0x00;
        $this->sendCommand([VL53L0XOpCode::VHV_CONFIG_PAD_SCL_SDA_EXTSUP_HV->value, $value]);
        $this->sendCommand([VL53L0XOpCode::I2C_MODE_CONTROL->value, 0x00]);
    }

    public function readDeviceId(): int
    {
        [$device_id] = $this->readData(VL53L0XCommandRegister::IDENTIFICATION_MODEL_ID->value, 1);

        return $device_id;
    }

    public function readRevisionId(): int
    {
        [$revision_id] = $this->readData(VL53L0XCommandRegister::IDENTIFICATION_REVISION_ID->value, 1);

        return $revision_id;
    }

    public function powerOn(): void
    {
        $this->sendCommand([VL53L0XOpCode::POWER_MANAGEMENT_GO1_POWER_FORCE->value, 0x01]);
        $this->sendCommand([VL53L0XOpCode::PAGE_SELECT->value, 0x01]);
        $this->sendCommand([VL53L0XOpCode::SYSRANGE_START->value, 0x00]);

        [$this->stop_var] = $this->readData(VL53L0XP1CommandRegister::STOP_VARIABLE->value, 1);

        $this->sendCommand([VL53L0XOpCode::SYSRANGE_START->value, 0x01]);
        $this->sendCommand([VL53L0XOpCode::PAGE_SELECT->value, 0x00]);
        $this->sendCommand([VL53L0XOpCode::POWER_MANAGEMENT_GO1_POWER_FORCE->value, 0x00]);
    }

    public function setSequenceConfig(): void
    {
        $this->sendCommand([VL53L0XOpCode::SYSTEM_SEQUENCE_CONFIG->value, $this->sequenceConfig()]);
    }

    public function setLimitCheckValue(int $checkId, int $value): void
    {
        switch ($checkId) {
            case 0: // SIGMA_FINAL_RANGE — software only
                $this->sigma_limit = $value;
                break;

            case 1: // SIGNAL_RATE_FINAL_RANGE — write to register 0x44 (little-endian FixPoint97)
                $converted = ($value >> 9) & 0xFFFF;
                $low = $converted & 0xFF;
                $high = ($converted >> 8) & 0xFF;
                $this->sendCommand([VL53L0XOpCode::FINAL_RANGE_CONFIG_MIN_COUNT_RATE_RTN_LIMIT->value, $low, $high]);
                break;

            case 2: // SIGNAL_REF_CLIP — software only
                $this->signal_ref_clip = $value;
                break;

            case 3: // RANGE_IGNORE_THRESHOLD — software only
                $this->range_ignore_threshold = $value;
                break;
        }
    }

    /**
     * @throws VL53L0XException
     */
    public function readNVMInfo(): void
    {
        $this->sendCommand([VL53L0XOpCode::POWER_MANAGEMENT_GO1_POWER_FORCE->value, 0x01]);
        $this->sendCommand([VL53L0XOpCode::PAGE_SELECT->value, 0x01]);
        $this->sendCommand([VL53L0XOpCode::SYSRANGE_START->value, 0x00]);

        $this->sendCommand([VL53L0XOpCode::PAGE_SELECT->value, 0x06]);
        [$byte] = $this->readData(VL53L0XP1CommandRegister::NVM_BIST_CTRL->value, 1);
        $this->sendCommand([VL53L0XP1CommandRegister::NVM_BIST_CTRL->value, $byte | 0x04]);

        $this->sendCommand([VL53L0XOpCode::PAGE_SELECT->value, 0x07]);
        $this->sendCommand([VL53L0XP1CommandRegister::NVM_CTRL_STATUS->value, 0x01]);

        usleep(1_000);

        $this->sendCommand([VL53L0XOpCode::POWER_MANAGEMENT_GO1_POWER_FORCE->value, 0x01]);

        // Read SPAD count and aperture type
        $this->sendCommand([VL53L0XP1CommandRegister::NVM_READ_COMMAND->value, VL53L0XP1CommandRegister::NVM_SPAD_INFO->value]);
        $this->deviceReadStrobe();
        [$b0, $b1, $b2, $b3] = $this->readData(VL53L0XP1CommandRegister::NVM_DATA_OUT->value, 4);
        $tmpDword = ($b3 << 24) | ($b2 << 16) | ($b1 << 8) | $b0;

        $this->ref_spad_count = ($tmpDword >> 8) & 0x7F;
        $this->ref_spad_type = ($tmpDword >> 15) & 0x01;

        // Read good SPAD map (bytes 0–3)
        $this->sendCommand([VL53L0XP1CommandRegister::NVM_READ_COMMAND->value, VL53L0XP1CommandRegister::NVM_GOOD_SPAD_MAP_0->value]);
        $this->deviceReadStrobe();
        [$b0, $b1, $b2, $b3] = $this->readData(VL53L0XP1CommandRegister::NVM_DATA_OUT->value, 4);
        $tmpDword = ($b3 << 24) | ($b2 << 16) | ($b1 << 8) | $b0;
        $this->nvm_ref_good_spad_map[0] = ($tmpDword >> 24) & 0xFF;
        $this->nvm_ref_good_spad_map[1] = ($tmpDword >> 16) & 0xFF;
        $this->nvm_ref_good_spad_map[2] = ($tmpDword >> 8) & 0xFF;
        $this->nvm_ref_good_spad_map[3] = $tmpDword & 0xFF;

        // Read good SPAD map (bytes 4–5)
        $this->sendCommand([VL53L0XP1CommandRegister::NVM_READ_COMMAND->value, VL53L0XP1CommandRegister::NVM_GOOD_SPAD_MAP_4->value]);
        $this->deviceReadStrobe();
        [$b0, $b1, $b2, $b3] = $this->readData(VL53L0XP1CommandRegister::NVM_DATA_OUT->value, 4);
        $tmpDword = ($b3 << 24) | ($b2 << 16) | ($b1 << 8) | $b0;
        $this->nvm_ref_good_spad_map[4] = ($tmpDword >> 24) & 0xFF;
        $this->nvm_ref_good_spad_map[5] = ($tmpDword >> 16) & 0xFF;

        // Exit NVM read mode
        $this->sendCommand([VL53L0XP1CommandRegister::NVM_CTRL_STATUS->value, 0x00]);
        $this->sendCommand([VL53L0XOpCode::PAGE_SELECT->value, 0x06]);
        [$byte] = $this->readData(VL53L0XP1CommandRegister::NVM_BIST_CTRL->value, 1);
        $this->sendCommand([VL53L0XP1CommandRegister::NVM_BIST_CTRL->value, $byte & 0xFB]);
        $this->sendCommand([VL53L0XOpCode::PAGE_SELECT->value, 0x01]);
        $this->sendCommand([VL53L0XOpCode::SYSRANGE_START->value, 0x01]);
        $this->sendCommand([VL53L0XOpCode::PAGE_SELECT->value, 0x00]);
        $this->sendCommand([VL53L0XOpCode::POWER_MANAGEMENT_GO1_POWER_FORCE->value, 0x00]);
    }

    /**
     * @throws VL53L0XException
     */
    public function performRefSpadManagement(): void
    {
        $R = VL53L0XOpCode::class;

        $targetRefRate = 0x0A00;
        $startSelect = 0xB4;
        $minimumSpadCount = 3;
        $maxSpadCount = 44;

        $refSpadEnables = [0, 0, 0, 0, 0, 0];

        $this->sendCommand([$R::PAGE_SELECT->value, 0x01]);
        $this->sendCommand([$R::DYNAMIC_SPAD_REF_EN_START_OFFSET->value, 0x00]);
        $this->sendCommand([$R::DYNAMIC_SPAD_NUM_REQUESTED_REF_SPAD->value, 0x2C]);
        $this->sendCommand([$R::PAGE_SELECT->value, 0x00]);
        $this->sendCommand([$R::GLOBAL_CONFIG_REF_EN_START_SELECT->value, $startSelect]);
        $this->sendCommand([$R::POWER_MANAGEMENT_GO1_POWER_FORCE->value, 0x00]);

        $currentSpadIndex = 0;
        $needApertureSpads = false;

        for ($i = 0; $i < $minimumSpadCount; $i++) {
            $nextGoodSpad = $this->getNextGoodSpad($currentSpadIndex);
            $this->enableSpadBit($refSpadEnables, $nextGoodSpad);
            $currentSpadIndex = $nextGoodSpad + 1;
        }

        $this->writeSpadEnables($refSpadEnables);
        $peakSignalRate = $this->performRefSignalMeasurement();

        if ($peakSignalRate > $targetRefRate) {
            $needApertureSpads = true;
            $refSpadEnables = [0, 0, 0, 0, 0, 0];
            $currentSpadIndex = 0;

            while (! $this->isApertureSpad($startSelect + $currentSpadIndex) && $currentSpadIndex < $maxSpadCount) {
                $currentSpadIndex++;
            }

            for ($i = 0; $i < $minimumSpadCount; $i++) {
                $nextGoodSpad = $this->getNextGoodSpad($currentSpadIndex);
                $this->enableSpadBit($refSpadEnables, $nextGoodSpad);
                $currentSpadIndex = $nextGoodSpad + 1;
            }

            $this->writeSpadEnables($refSpadEnables);
            $peakSignalRate = $this->performRefSignalMeasurement();
        }

        $refSpadCount = $minimumSpadCount;
        $lastSignalRateDiff = abs($peakSignalRate - $targetRefRate);
        $lastSpadArray = $refSpadEnables;

        while ($peakSignalRate < $targetRefRate && $refSpadCount < $maxSpadCount) {
            $lastSpadArray = $refSpadEnables;

            $nextGoodSpad = $this->getNextGoodSpad($currentSpadIndex);
            if ($nextGoodSpad === -1) {
                break;
            }

            if ($this->isApertureSpad($startSelect + $nextGoodSpad) !== $needApertureSpads) {
                break;
            }

            $this->enableSpadBit($refSpadEnables, $nextGoodSpad);
            $refSpadCount++;
            $currentSpadIndex = $nextGoodSpad + 1;

            $this->writeSpadEnables($refSpadEnables);
            $peakSignalRate = $this->performRefSignalMeasurement();

            $signalRateDiff = abs($peakSignalRate - $targetRefRate);

            if ($peakSignalRate > $targetRefRate && $signalRateDiff > $lastSignalRateDiff) {
                $this->writeSpadEnables($lastSpadArray);
                $refSpadCount--;
                break;
            }

            $lastSignalRateDiff = $signalRateDiff;
        }

        $this->ref_spad_count = $refSpadCount;
        $this->ref_spad_type = $needApertureSpads ? 1 : 0;
    }

    /**
     * @throws VL53L0XException
     */
    public function performRefCalibration(): void
    {
        $R = VL53L0XOpCode::class;

        // VHV calibration (vhv_init_byte 0x40 selects VHV)
        $this->sendCommand([$R::SYSTEM_SEQUENCE_CONFIG->value, 0x01]);
        $this->performSingleRefCalibration(0x40);

        // Phase calibration
        $this->sendCommand([$R::SYSTEM_SEQUENCE_CONFIG->value, 0x02]);
        $this->performSingleRefCalibration(0x00);

        // Restore the full sequence config
        $this->sendCommand([$R::SYSTEM_SEQUENCE_CONFIG->value, 0xE8]);
    }

    public function kickOffMeasurement(): void
    {
        $this->sendCommand([VL53L0XOpCode::SYSRANGE_START->value, 0x01]);
    }

    /**
     * @throws VL53L0XException
     */
    private function performSingleRefCalibration(int $vhvInitByte): void
    {
        $R = VL53L0XOpCode::class;

        $this->sendCommand([$R::SYSRANGE_START->value, 0x01 | $vhvInitByte]);

        $this->awaitMeasurementComplete();

        $this->sendCommand([$R::SYSTEM_INTERRUPT_CLEAR->value, 0x01]);
        $this->sendCommand([$R::SYSRANGE_START->value, 0x00]);
    }

    /**
     * Polls the data-ready interrupt until the device reports a completed
     * sample. While an on-chip measurement is in flight the VL53L0X keeps the
     * I2C bus busy and can briefly fail to respond, so a failed read here is
     * not an error - it just means "not ready yet". We keep polling until the
     * sample lands or the timeout elapses.
     *
     * @throws VL53L0XException when no sample arrives within the timeout
     */
    private function awaitMeasurementComplete(int $timeoutMs = 1000): void
    {
        $startedAt = microtime(true);

        do {
            $status = $this->readInterruptStatusOrNull();

            if (! is_null($status) && ($status & 0x07) !== 0) {
                return;
            }

            usleep(1_000);
        } while (((microtime(true) - $startedAt) * 1000.0) < $timeoutMs);

        throw VL53L0XException::measurementTimeout();
    }

    /**
     * Reads RESULT_INTERRUPT_STATUS, returning null instead of throwing when
     * the bus read fails - used only while polling an in-flight measurement
     * that may not be responding yet.
     */
    private function readInterruptStatusOrNull(): ?int
    {
        try {
            [$status] = $this->readData(VL53L0XCommandRegister::RESULT_INTERRUPT_STATUS->value, 1);

            return $status;
        } catch (VL53L0XException) {
            return null;
        }
    }

    public function setDeviceMode(): void
    {
        $this->sendCommand([VL53L0XOpCode::SYSRANGE_START->value, 0x00]);
    }

    /**
     * @throws VL53L0XException
     */
    public function statusReady(): bool
    {
        $status = $this->readInterruptStatusOrNull();

        // A transient bus failure means the device is still busy with the
        // measurement - report "not ready yet" so the caller keeps polling.
        if (is_null($status)) {
            return false;
        }

        return ($status & 0x07) !== 0;
    }

    /**
     * @throws VL53L0XException
     */
    public function readStatus(): int
    {
        $data = $this->readData(VL53L0XCommandRegister::RESULT_RANGE_STATUS->value, 12);

        return (($data[10] ?? 0) << 8) | ($data[11] ?? 0);
    }

    public function clearInterrupt(): void
    {
        $this->sendCommand([VL53L0XOpCode::SYSTEM_INTERRUPT_CLEAR->value, 0x01]);
    }

    protected function loadTuningSettings(): void
    {
        $R = VL53L0XOpCode::class;
        $T = VL53L0XTuningCommandRegister::class;
        $P = VL53L0XP1TuningCommandRegister::class;

        $tuning = [
            [$R::PAGE_SELECT->value, 0x01],
            [$P::CONTROL_REGISTER->value, 0x00],

            [$R::PAGE_SELECT->value, 0x00],
            [$R::SYSTEM_RANGE_CONFIG->value, 0x00],
            [$T::INTERNAL_TUNING_1->value, 0x00],
            [$T::INTERNAL_TUNING_2->value, 0x00],

            [$T::INTERNAL_TUNING_5->value, 0x01],
            [$T::INTERNAL_TUNING_6->value, 0xFF],
            [$T::INTERNAL_TUNING_19->value, 0x00],

            [$R::PAGE_SELECT->value, 0x01],
            [$P::P1_TUNING_20->value, 0x2C],
            [$P::P1_TUNING_14->value, 0x00],
            [$P::P1_ALGO_PHASECAL_LIM->value, 0x20],

            [$R::PAGE_SELECT->value, 0x00],
            [$R::ALGO_PHASECAL_LIM->value, 0x09],
            [$T::INTERNAL_TUNING_19->value, 0x00],
            [$T::INTERNAL_TUNING_7->value, 0x04],
            [$R::GLOBAL_CONFIG_VCSEL_WIDTH->value, 0x03],
            [$T::INTERNAL_TUNING_10->value, 0x83],
            [$R::MSRC_CONFIG_TIMEOUT_MACROP->value, 0x25],
            [$R::MSRC_CONFIG_CONTROL->value, 0x00],
            [$R::PRE_RANGE_CONFIG_MIN_SNR->value, 0x00],
            [$R::PRE_RANGE_CONFIG_VCSEL_PERIOD->value, 0x06],
            [$R::PRE_RANGE_CONFIG_TIMEOUT_MACROP_HI->value, 0x00],
            [$R::PRE_RANGE_CONFIG_TIMEOUT_MACROP_LO->value, 0x96],
            [$R::PRE_RANGE_CONFIG_VALID_PHASE_LOW->value, 0x08],
            [$R::PRE_RANGE_CONFIG_VALID_PHASE_HIGH->value, 0x30],
            [$R::PRE_RANGE_CONFIG_SIGMA_THRESH_HI->value, 0x00],
            [$R::PRE_RANGE_CONFIG_SIGMA_THRESH_LO->value, 0x00],
            [$R::PRE_RANGE_MIN_COUNT_RATE_RTN_LIMIT->value, 0x00],
            [$T::INTERNAL_TUNING_29->value, 0x00],
            [$T::INTERNAL_TUNING_30->value, 0xA0],

            [$R::PAGE_SELECT->value, 0x01],
            [$P::P1_TUNING_4->value, 0x32],
            [$P::P1_TUNING_13->value, 0x14],
            [$P::P1_TUNING_15->value, 0xFF],
            [$P::P1_TUNING_16->value, 0x00],

            [$R::PAGE_SELECT->value, 0x00],
            [$T::INTERNAL_TUNING_24->value, 0x0A],
            [$T::INTERNAL_TUNING_25->value, 0x00],
            [$T::INTERNAL_TUNING_23->value, 0x21],

            [$R::PAGE_SELECT->value, 0x01],
            [$P::P1_TUNING_5->value, 0x34],
            [$P::P1_TUNING_8->value, 0x00],
            [$P::P1_TUNING_10->value, 0xFF],
            [$P::P1_TUNING_11->value, 0x26],
            [$P::P1_TUNING_12->value, 0x05],
            [$P::P1_TUNING_7->value, 0x40],
            [$P::P1_TUNING_2->value, 0x06],
            [$P::P1_TUNING_3->value, 0x1A],
            [$P::P1_TUNING_9->value, 0x40],

            [$R::PAGE_SELECT->value, 0x00],
            [$T::INTERNAL_TUNING_8->value, 0x03],
            [$T::INTERNAL_TUNING_9->value, 0x44],

            [$R::PAGE_SELECT->value, 0x01],
            [$P::P1_TUNING_6->value, 0x04],
            [$P::P1_TUNING_17->value, 0x09],
            [$P::P1_TUNING_18->value, 0x05],
            [$P::P1_TUNING_19->value, 0x04],

            [$R::PAGE_SELECT->value, 0x00],
            [$R::FINAL_RANGE_CONFIG_MIN_COUNT_RATE_RTN_LIMIT->value, 0x00],
            [$T::INTERNAL_TUNING_13->value, 0x20],
            [$R::FINAL_RANGE_CONFIG_VALID_PHASE_LOW->value, 0x08],
            [$R::FINAL_RANGE_CONFIG_VALID_PHASE_HIGH->value, 0x28],
            [$R::FINAL_RANGE_CONFIG_MIN_SNR->value, 0x00],
            [$R::FINAL_RANGE_CONFIG_VCSEL_PERIOD->value, 0x04],
            [$R::FINAL_RANGE_CONFIG_TIMEOUT_MACROP_HI->value, 0x01],
            [$R::FINAL_RANGE_CONFIG_TIMEOUT_MACROP_LO->value, 0xFE],
            [$T::INTERNAL_TUNING_21->value, 0x00],
            [$T::INTERNAL_TUNING_22->value, 0x00],

            [$R::PAGE_SELECT->value, 0x01],
            [$P::P1_TUNING_1->value, 0x01],

            [$R::PAGE_SELECT->value, 0x00],
            [$R::POWER_MANAGEMENT_GO1_POWER_FORCE->value, 0x01],
            [$R::SYSTEM_SEQUENCE_CONFIG->value, 0xF8],

            [$R::PAGE_SELECT->value, 0x01],
            [$P::P1_TUNING_21->value, 0x01],
            [$P::CONTROL_REGISTER->value, 0x01],

            [$R::PAGE_SELECT->value, 0x00],
            [$R::POWER_MANAGEMENT_GO1_POWER_FORCE->value, 0x00],
        ];

        foreach ($tuning as $cmd) {
            $this->sendCommand($cmd);
        }
    }

    private function deviceReadStrobe(): void
    {
        $this->sendCommand([VL53L0XP1CommandRegister::NVM_BIST_CTRL->value, 0x00]);

        $loop = 0;
        do {
            [$strobe] = $this->readData(VL53L0XP1CommandRegister::NVM_BIST_CTRL->value, 1);
            if ($strobe !== 0x00) {
                break;
            }
            $loop++;
        } while ($loop < 200);

        if ($loop >= 200) {
            throw VL53L0XException::strobeTimeout();
        }

        $this->sendCommand([VL53L0XP1CommandRegister::NVM_BIST_CTRL->value, 0x01]);
    }

    private function getNextGoodSpad(int $startIndex): int
    {
        for ($i = $startIndex; $i < 48; $i++) {
            $byteIndex = intdiv($i, 8);
            $bitIndex = $i % 8;
            if (($this->nvm_ref_good_spad_map[$byteIndex] ?? 0) >> $bitIndex & 0x1) {
                return $i;
            }
        }

        return -1;
    }

    private function enableSpadBit(array &$spadArray, int $spadIndex): void
    {
        $byteIndex = intdiv($spadIndex, 8);
        $bitIndex = $spadIndex % 8;
        $spadArray[$byteIndex] |= (1 << $bitIndex);
    }

    private function writeSpadEnables(array $spadArray): void
    {
        $this->sendCommand([
            VL53L0XOpCode::GLOBAL_CONFIG_SPAD_ENABLES_REF_0->value,
            $spadArray[0],
            $spadArray[1],
            $spadArray[2],
            $spadArray[3],
            $spadArray[4],
            $spadArray[5],
        ]);
    }

    private function performRefSignalMeasurement(): int
    {
        $R = VL53L0XOpCode::class;

        $this->sendCommand([$R::SYSTEM_SEQUENCE_CONFIG->value, 0xC0]);
        $this->sendCommand([$R::SYSRANGE_START->value, 0x01]);

        $this->awaitMeasurementComplete();

        $this->sendCommand([$R::SYSTEM_INTERRUPT_CLEAR->value, 0x01]);
        $this->sendCommand([$R::SYSRANGE_START->value, 0x00]);

        [$low, $high] = $this->readData($R::RESULT_CORE_AMBIENT_WINDOW_EVENTS_RTN->value, 2);
        $peakSignalRate = ($high << 8) | $low;

        $this->sendCommand([$R::SYSTEM_SEQUENCE_CONFIG->value, 0xE8]);

        return $peakSignalRate;
    }

    private function isApertureSpad(int $spadIndex): bool
    {
        return $spadIndex > 127;
    }
}
