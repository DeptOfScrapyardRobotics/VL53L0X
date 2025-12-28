<?php

namespace ScrapyardIO\Sensors\Distance\VL53L0X\Concerns;

use ScrapyardIO\Displays\Colors\Color;
use ScrapyardIO\Sensors\Distance\VL53L0X\Enums\VL53L0XCommand;
use ScrapyardIO\Sensors\Distance\VL53L0X\Enums\VL53L0XP1Command;
use ScrapyardIO\Sensors\Distance\VL53L0X\Enums\VL53L0XP1TuningCommand;
use ScrapyardIO\Sensors\Distance\VL53L0X\Enums\VL53L0XTuningCommand;
use ScrapyardIO\Sensors\Distance\VL53L0X\Exceptions\VL53L0XException;
use ScrapyardIO\Support\DataManipulation\ByteRegister;
use ScrapyardIO\Displays\Color\ST7796\Enums\ST7796Command;
use ScrapyardIO\Displays\Color\ST7796\Enums\ST7796ColorMode;

trait VL53L0XBootSequence
{
    protected ?int $stop_var = null;
    protected ?int $osc_freq = null;
    protected ?int $sigma_limit = null;
    protected bool $i2c_2v8_mode = true;
    protected ?int $ref_spad_type = null;
    protected ?int $ref_spad_count = null;
    protected ?int $signal_ref_clip = null;
    protected ?int $range_ignore_threshold = null;
    protected array $nvm_ref_good_spad_map = [];
    protected ?int $vhv_settings = null;
    protected ?int $phase_cal = null;

    protected bool $tcc_enabled = true;
    protected bool $msrc_enabled = true;
    protected bool $dss_enabled = true;
    protected bool $pre_range_enabled = true;
    protected bool $final_range_enabled = true;

    /**
     * @return void
     * @throws VL53L0XException
     */
    public function readDeviceId(): void
    {
        [$device_id] = $this->readData(VL53L0XCommand::IDENTIFICATION_MODEL_ID->value, 1);
        if($device_id != 0xEE) throw VL53L0XException::invalidDeviceID($device_id, 0xEE);
    }

    /**
     * @return void
     * @throws VL53L0XException
     */
    public function readRevisionId(): void
    {
        [$device_id] = $this->readData(VL53L0XCommand::IDENTIFICATION_REVISION_ID->value, 1);
        if($device_id != 0x10) throw VL53L0XException::invalidRevisionID($device_id, 0x10);
    }

    public function setI2CVoltageMode(): void
    {
        $value = $this->i2c_2v8_mode ? 0x01 : 0x00;
        $this->sendCommand([VL53L0XCommand::VHV_CONFIG_PAD_SCL_SDA__EXTSUP_HV->value, $value]);
        $this->sendCommand([VL53L0XCommand::I2C_MODE_CONTROL->value, 0x00]);
    }

    public function powerOn(): void
    {
        $this->sendCommand([VL53L0XCommand::POWER_MANAGEMENT_GO1_POWER_FORCE->value, 0x01]);
        $this->sendCommand([VL53L0XCommand::PAGE_SELECT->value, 0x01]);
        $this->sendCommand([VL53L0XCommand::SYSRANGE_START->value, 0x00]);

        [$this->stop_var] = $this->readData(VL53L0XP1Command::STOP_VARIABLE->value, 1);

        $this->sendCommand([VL53L0XCommand::SYSRANGE_START->value, 0x01]);
        $this->sendCommand([VL53L0XCommand::PAGE_SELECT->value, 0x00]);
        $this->sendCommand([VL53L0XCommand::POWER_MANAGEMENT_GO1_POWER_FORCE->value, 0x00]);
    }

    public function setSequenceConfig(): void
    {
        $this->sendCommand([VL53L0XCommand::SYSTEM_SEQUENCE_CONFIG->value, $this->sequenceConfig()]);
    }

    public function setLimitCheckValue(int $check_id, int $value): void
    {
        switch($check_id) {
            case 0: // SIGMA_FINAL_RANGE
                // NO hardware write - just store it
                $this->sigma_limit = $value;
                break;

            case 1: // SIGNAL_RATE_FINAL_RANGE
                // YES - write to register 0x44
                $converted = ($value >> 9) & 0xFFFF; // Convert FixPoint1616 to FixPoint97
                $low = $converted & 0xFF;
                $high = ($converted >> 8) & 0xFF;
                $this->sendCommand([VL53L0XCommand::FINAL_RANGE_CONFIG_MIN_COUNT_RATE_RTN_LIMIT->value, $low, $high]);
                break;

            case 2: // SIGNAL_REF_CLIP
                // NO hardware write - just store it
                $this->signal_ref_clip = $value;
                break;

            case 3: // RANGE_IGNORE_THRESHOLD
                // NO hardware write - just store it
                $this->range_ignore_threshold = $value;
                break;
        }
    }

    protected function sequenceConfig(): int
    {
        $register = new ByteRegister(0);
        return $register
            ->update(7, $this->final_range_enabled)
            ->update(6, $this->pre_range_enabled)
            ->update(4, $this->tcc_enabled)
            ->update(3, $this->dss_enabled)
            ->update(2, $this->msrc_enabled)
            ->byte;
    }

    protected function loadTuningSettings(): void
    {
        // Complete DefaultTuningSettings from vl53l0x_tuning.h
        // Format: [register_address, data_byte(s)]
        $tuning = [
            // Switch to Page 1
            [VL53L0XCommand::PAGE_SELECT->value, 0x01],
            [VL53L0XP1TuningCommand::CONTROL_REGISTER->value, 0x00],

            // Switch to Page 0
            [VL53L0XCommand::PAGE_SELECT->value, 0x00],
            [VL53L0XCommand::SYSTEM_RANGE_CONFIG->value, 0x00],
            [VL53L0XTuningCommand::INTERNAL_TUNING_1->value, 0x00],
            [VL53L0XTuningCommand::INTERNAL_TUNING_2->value, 0x00],

            [VL53L0XTuningCommand::INTERNAL_TUNING_5->value, 0x01],
            [VL53L0XTuningCommand::INTERNAL_TUNING_6->value, 0xFF],
            [VL53L0XTuningCommand::INTERNAL_TUNING_19->value, 0x00],

            // Switch to Page 1
            [VL53L0XCommand::PAGE_SELECT->value, 0x01],
            [VL53L0XP1TuningCommand::P1_TUNING_20->value, 0x2C],
            [VL53L0XP1TuningCommand::P1_TUNING_14->value, 0x00],
            [VL53L0XP1TuningCommand::P1_ALGO_PHASECAL_LIM->value, 0x20],

            // Switch to Page 0
            [VL53L0XCommand::PAGE_SELECT->value, 0x00],
            [VL53L0XCommand::ALGO_PHASECAL_LIM->value, 0x09],
            [VL53L0XTuningCommand::INTERNAL_TUNING_19->value, 0x00],
            [VL53L0XTuningCommand::INTERNAL_TUNING_7->value, 0x04],
            [VL53L0XCommand::GLOBAL_CONFIG_VCSEL_WIDTH->value, 0x03],
            [VL53L0XTuningCommand::INTERNAL_TUNING_10->value, 0x83],
            [VL53L0XCommand::MSRC_CONFIG_TIMEOUT_MACROP->value, 0x25],
            [VL53L0XCommand::MSRC_CONFIG_CONTROL->value, 0x00],
            [VL53L0XCommand::PRE_RANGE_CONFIG_MIN_SNR->value, 0x00],
            [VL53L0XCommand::PRE_RANGE_CONFIG_VCSEL_PERIOD->value, 0x06],
            [VL53L0XCommand::PRE_RANGE_CONFIG_TIMEOUT_MACROP_HI->value, 0x00],
            [VL53L0XCommand::PRE_RANGE_CONFIG_TIMEOUT_MACROP_LO->value, 0x96],
            [VL53L0XCommand::PRE_RANGE_CONFIG_VALID_PHASE_LOW->value, 0x08],
            [VL53L0XCommand::PRE_RANGE_CONFIG_VALID_PHASE_HIGH->value, 0x30],
            [VL53L0XCommand::PRE_RANGE_CONFIG_SIGMA_THRESH_HI->value, 0x00],
            [VL53L0XCommand::PRE_RANGE_CONFIG_SIGMA_THRESH_LO->value, 0x00],
            [VL53L0XCommand::PRE_RANGE_MIN_COUNT_RATE_RTN_LIMIT->value, 0x00],
            [VL53L0XTuningCommand::INTERNAL_TUNING_29->value, 0x00],
            [VL53L0XTuningCommand::INTERNAL_TUNING_30->value, 0xA0],

            // Switch to Page 1
            [VL53L0XCommand::PAGE_SELECT->value, 0x01],
            [VL53L0XP1TuningCommand::P1_TUNING_4->value, 0x32],
            [VL53L0XP1TuningCommand::P1_TUNING_13->value, 0x14],
            [VL53L0XP1TuningCommand::P1_TUNING_15->value, 0xFF],
            [VL53L0XP1TuningCommand::P1_TUNING_16->value, 0x00],

            // Switch to Page 0
            [VL53L0XCommand::PAGE_SELECT->value, 0x00],
            [VL53L0XTuningCommand::INTERNAL_TUNING_24->value, 0x0A],
            [VL53L0XTuningCommand::INTERNAL_TUNING_25->value, 0x00],
            [VL53L0XTuningCommand::INTERNAL_TUNING_23->value, 0x21],

            // Switch to Page 1
            [VL53L0XCommand::PAGE_SELECT->value, 0x01],
            [VL53L0XP1TuningCommand::P1_TUNING_5->value, 0x34],
            [VL53L0XP1TuningCommand::P1_TUNING_8->value, 0x00],
            [VL53L0XP1TuningCommand::P1_TUNING_10->value, 0xFF],
            [VL53L0XP1TuningCommand::P1_TUNING_11->value, 0x26],
            [VL53L0XP1TuningCommand::P1_TUNING_12->value, 0x05],
            [VL53L0XP1TuningCommand::P1_TUNING_7->value, 0x40],
            [VL53L0XP1TuningCommand::P1_TUNING_2->value, 0x06],
            [VL53L0XP1TuningCommand::P1_TUNING_3->value, 0x1A],
            [VL53L0XP1TuningCommand::P1_TUNING_9->value, 0x40],

            // Switch to Page 0
            [VL53L0XCommand::PAGE_SELECT->value, 0x00],
            [VL53L0XTuningCommand::INTERNAL_TUNING_8->value, 0x03],
            [VL53L0XTuningCommand::INTERNAL_TUNING_9->value, 0x44],

            // Switch to Page 1
            [VL53L0XCommand::PAGE_SELECT->value, 0x01],
            [VL53L0XP1TuningCommand::P1_TUNING_6->value, 0x04],
            [VL53L0XP1TuningCommand::P1_TUNING_17->value, 0x09],
            [VL53L0XP1TuningCommand::P1_TUNING_18->value, 0x05],
            [VL53L0XP1TuningCommand::P1_TUNING_19->value, 0x04],

            // Switch to Page 0
            [VL53L0XCommand::PAGE_SELECT->value, 0x00],
            [VL53L0XCommand::FINAL_RANGE_CONFIG_MIN_COUNT_RATE_RTN_LIMIT->value, 0x00],
            [VL53L0XTuningCommand::INTERNAL_TUNING_13->value, 0x20],
            [VL53L0XCommand::FINAL_RANGE_CONFIG_VALID_PHASE_LOW->value, 0x08],
            [VL53L0XCommand::FINAL_RANGE_CONFIG_VALID_PHASE_HIGH->value, 0x28],
            [VL53L0XCommand::FINAL_RANGE_CONFIG_MIN_SNR->value, 0x00],
            [VL53L0XCommand::FINAL_RANGE_CONFIG_VCSEL_PERIOD->value, 0x04],
            [VL53L0XCommand::FINAL_RANGE_CONFIG_TIMEOUT_MACROP_HI->value, 0x01],
            [VL53L0XCommand::FINAL_RANGE_CONFIG_TIMEOUT_MACROP_LO->value, 0xFE],
            [VL53L0XTuningCommand::INTERNAL_TUNING_21->value, 0x00],
            [VL53L0XTuningCommand::INTERNAL_TUNING_22->value, 0x00],

            // Switch to Page 1
            [VL53L0XCommand::PAGE_SELECT->value, 0x01],
            [VL53L0XP1TuningCommand::P1_TUNING_1->value, 0x01],

            // Switch to Page 0
            [VL53L0XCommand::PAGE_SELECT->value, 0x00],
            [VL53L0XCommand::POWER_MANAGEMENT_GO1_POWER_FORCE->value, 0x01],
            [VL53L0XCommand::SYSTEM_SEQUENCE_CONFIG->value, 0xF8],

            // Switch to Page 1
            [VL53L0XCommand::PAGE_SELECT->value, 0x01],
            [VL53L0XP1TuningCommand::P1_TUNING_21->value, 0x01],
            [VL53L0XP1TuningCommand::CONTROL_REGISTER->value, 0x01],

            // Switch back to Page 0
            [VL53L0XCommand::PAGE_SELECT->value, 0x00],
            [VL53L0XCommand::POWER_MANAGEMENT_GO1_POWER_FORCE->value, 0x00],
        ];

        foreach($tuning as $cmd) {
            $this->sendCommand($cmd);
        }
    }

    /**
     * @return void
     * @throws VL53L0XException
     */
    public function readNVMInfo(): void
    {
        // Enter special NVM read mode
        $this->sendCommand([VL53L0XCommand::POWER_MANAGEMENT_GO1_POWER_FORCE->value, 0x01]);
        $this->sendCommand([VL53L0XCommand::PAGE_SELECT->value, 0x01]);
        $this->sendCommand([VL53L0XCommand::SYSRANGE_START->value, 0x00]);

        // Enable NVM read
        $this->sendCommand([VL53L0XCommand::PAGE_SELECT->value, 0x06]);
        [$byte] = $this->readData(VL53L0XP1Command::NVM_BIST_CTRL->value, 1);
        $this->sendCommand([VL53L0XP1Command::NVM_BIST_CTRL->value, $byte | 0x04]);

        $this->sendCommand([VL53L0XCommand::PAGE_SELECT->value, 0x07]);
        $this->sendCommand([VL53L0XP1Command::NVM_CTRL_STATUS->value, 0x01]);

        usleep(1000); // Wait 1ms

        $this->sendCommand([VL53L0XCommand::POWER_MANAGEMENT_GO1_POWER_FORCE->value, 0x01]);

        // Read SPAD info from NVM
        $this->sendCommand([VL53L0XP1Command::NVM_READ_COMMAND->value, VL53L0XP1Command::NVM_SPAD_INFO->value]);
        $this->deviceReadStrobe();
        [$b0, $b1, $b2, $b3] = $this->readData(VL53L0XP1Command::NVM_DATA_OUT->value, 4);
        $tmp_dword = ($b3 << 24) | ($b2 << 16) | ($b1 << 8) | $b0;

        $this->ref_spad_count = ($tmp_dword >> 8) & 0x7F;
        $this->ref_spad_type = ($tmp_dword >> 15) & 0x01;

        // Read good SPAD map
        $this->sendCommand([VL53L0XP1Command::NVM_READ_COMMAND->value, VL53L0XP1Command::NVM_GOOD_SPAD_MAP_0->value]);
        $this->deviceReadStrobe();
        [$b0, $b1, $b2, $b3] = $this->readData(VL53L0XP1Command::NVM_DATA_OUT->value, 4);
        $tmp_dword = ($b3 << 24) | ($b2 << 16) | ($b1 << 8) | $b0;
        $this->nvm_ref_good_spad_map[0] = ($tmp_dword >> 24) & 0xFF;
        $this->nvm_ref_good_spad_map[1] = ($tmp_dword >> 16) & 0xFF;
        $this->nvm_ref_good_spad_map[2] = ($tmp_dword >> 8) & 0xFF;
        $this->nvm_ref_good_spad_map[3] = $tmp_dword & 0xFF;

        $this->sendCommand([VL53L0XP1Command::NVM_READ_COMMAND->value, VL53L0XP1Command::NVM_GOOD_SPAD_MAP_4->value]);
        $this->deviceReadStrobe();
        [$b0, $b1, $b2, $b3] = $this->readData(VL53L0XP1Command::NVM_DATA_OUT->value, 4);
        $tmp_dword = ($b3 << 24) | ($b2 << 16) | ($b1 << 8) | $b0;
        $this->nvm_ref_good_spad_map[4] = ($tmp_dword >> 24) & 0xFF;
        $this->nvm_ref_good_spad_map[5] = ($tmp_dword >> 16) & 0xFF;

        // Exit NVM read mode
        $this->sendCommand([VL53L0XP1Command::NVM_CTRL_STATUS->value, 0x00]);
        $this->sendCommand([VL53L0XCommand::PAGE_SELECT->value, 0x06]);
        [$byte] = $this->readData(VL53L0XP1Command::NVM_BIST_CTRL->value, 1);
        $this->sendCommand([VL53L0XP1Command::NVM_BIST_CTRL->value, $byte & 0xFB]);
        $this->sendCommand([VL53L0XCommand::PAGE_SELECT->value, 0x01]);
        $this->sendCommand([VL53L0XCommand::SYSRANGE_START->value, 0x01]);
        $this->sendCommand([VL53L0XCommand::PAGE_SELECT->value, 0x00]);
        $this->sendCommand([VL53L0XCommand::POWER_MANAGEMENT_GO1_POWER_FORCE->value, 0x00]);
    }

    /**
     * @return void
     * @throws VL53L0XException
     */
    private function deviceReadStrobe(): void
    {
        $this->sendCommand([VL53L0XP1Command::NVM_BIST_CTRL->value, 0x00]);

        // Poll until strobe completes
        $loop = 0;
        do
        {
            [$strobe] = $this->readData(VL53L0XP1Command::NVM_BIST_CTRL->value, 1);
            if($strobe != 0x00) break;
            $loop++;
        } while($loop < 200);

        if($loop >= 200)
        {
            throw VL53L0XException::strobeTimeout();
        }

        $this->sendCommand([VL53L0XP1Command::NVM_BIST_CTRL->value, 0x01]);
    }

    /**
     * @return void
     * @throws VL53L0XException
     */
    protected function dataInit(): void
    {
        $this->setI2CVoltageMode();

        $this->readDeviceId();
        $this->readRevisionId();

        $this->powerOn();
        $this->setSequenceConfig();
        $this->setLimitCheckValue(0, 18 * 65536);
        $this->setLimitCheckValue(1, (25 * 65536) / 100);
        $this->setLimitCheckValue(2, 35 * 65536);
        $this->setLimitCheckValue(3, 0);
    }

    /**
     * @return void
     * @throws VL53L0XException
     */
    protected function staticInit(): void
    {
        $this->readNVMInfo();
        $this->loadTuningSettings();

        $this->sendCommand([VL53L0XCommand::SYSTEM_INTERRUPT_CONFIG_GPIO->value, 0x04]);

        // Read oscillator frequency
        $this->sendCommand([VL53L0XCommand::PAGE_SELECT->value, 0x01]);
        [$osc_low, $osc_high] = $this->readData(0x84, 2);
        $this->sendCommand([VL53L0XCommand::PAGE_SELECT->value, 0x00]);
        $this->osc_freq = ($osc_high << 8) | $osc_low;

        // Disable MSRC and TCC steps
        $seq_config = 0xFF & ~0x10 & ~0x04; // Disable TCC (bit 4) and MSRC (bit 2)
        $this->sendCommand([VL53L0XCommand::SYSTEM_SEQUENCE_CONFIG->value, $seq_config]);
    }

    public function setDeviceMode(): void {
        $this->sendCommand([VL53L0XCommand::SYSRANGE_START->value, 0x00]); // Single ranging mode
    }

    public function kickOffMeasurement(): void {
        $this->sendCommand([VL53L0XCommand::SYSRANGE_START->value, 0x01]);
    }

    public function statusWait(int $timeout_ms = 0): bool
    {
        $start_time = microtime(true);
        while ($this->statusReady()) {
            usleep(1000); // 1ms sleep to avoid busy-waiting

            if ($timeout_ms > 0) {
                $elapsed_ms = (microtime(true) - $start_time) * 1000;
                if ($elapsed_ms >= $timeout_ms) {
                    return false; // Timeout reached
                }
            }
        }

        return true;
    }

    public function statusReady(): bool
    {
        [$byte] = $this->readData(VL53L0XCommand::RESULT_INTERRUPT_STATUS->value, 1);
        return ($byte & 0x07) == 0;
    }

    public function readStatus(): int
    {
        $data = $this->readData(VL53L0XCommand::RESULT_RANGE_STATUS->value, 12);
        return ($data[10] << 8) | $data[11];
    }

    public function clearInterrupt(): void
    {
        $this->sendCommand([VL53L0XCommand::SYSTEM_INTERRUPT_CLEAR->value, 0x01]);
    }


    public function performRefSpadManagement(): void
    {
        $target_ref_rate = 0x0A00;
        $start_select = 0xB4;
        $minimum_spad_count = 3;
        $max_spad_count = 44;

        $ref_spad_enables = [0, 0, 0, 0, 0, 0];

        $this->sendCommand([VL53L0XCommand::PAGE_SELECT->value, 0x01]);
        $this->sendCommand([VL53L0XCommand::DYNAMIC_SPAD_REF_EN_START_OFFSET->value, 0x00]);
        $this->sendCommand([VL53L0XCommand::DYNAMIC_SPAD_NUM_REQUESTED_REF_SPAD->value, 0x2C]);
        $this->sendCommand([VL53L0XCommand::PAGE_SELECT->value, 0x00]);
        $this->sendCommand([VL53L0XCommand::GLOBAL_CONFIG_REF_EN_START_SELECT->value, $start_select]);
        $this->sendCommand([VL53L0XCommand::POWER_MANAGEMENT_GO1_POWER_FORCE->value, 0x00]);

        $this->performRefCalibration();

        $current_spad_index = 0;
        $need_aperture_spads = false;

        for ($i = 0; $i < $minimum_spad_count; $i++) {
            $next_good_spad = $this->getNextGoodSpad($current_spad_index);
            $this->enableSpadBit($ref_spad_enables, $next_good_spad);
            $current_spad_index = $next_good_spad + 1;
        }

        $this->writeSpadEnables($ref_spad_enables);
        $peak_signal_rate = $this->performRefSignalMeasurement();

        if ($peak_signal_rate > $target_ref_rate) {
            $need_aperture_spads = true;
            $ref_spad_enables = [0, 0, 0, 0, 0, 0];
            $current_spad_index = 0;

            while (!$this->isApertureSpad($start_select + $current_spad_index) && $current_spad_index < $max_spad_count) {
                $current_spad_index++;
            }

            for ($i = 0; $i < $minimum_spad_count; $i++) {
                $next_good_spad = $this->getNextGoodSpad($current_spad_index);
                $this->enableSpadBit($ref_spad_enables, $next_good_spad);
                $current_spad_index = $next_good_spad + 1;
            }

            $this->writeSpadEnables($ref_spad_enables);
            $peak_signal_rate = $this->performRefSignalMeasurement();
        }

        $ref_spad_count = $minimum_spad_count;
        $last_signal_rate_diff = abs($peak_signal_rate - $target_ref_rate);
        $last_spad_array = $ref_spad_enables;

        while ($peak_signal_rate < $target_ref_rate && $ref_spad_count < $max_spad_count) {
            $last_spad_array = $ref_spad_enables;

            $next_good_spad = $this->getNextGoodSpad($current_spad_index);
            if ($next_good_spad == -1) break;

            if ($this->isApertureSpad($start_select + $next_good_spad) != $need_aperture_spads) {
                break;
            }

            $this->enableSpadBit($ref_spad_enables, $next_good_spad);
            $ref_spad_count++;
            $current_spad_index = $next_good_spad + 1;

            $this->writeSpadEnables($ref_spad_enables);
            $peak_signal_rate = $this->performRefSignalMeasurement();

            $signal_rate_diff = abs($peak_signal_rate - $target_ref_rate);

            if ($peak_signal_rate > $target_ref_rate && $signal_rate_diff > $last_signal_rate_diff) {
                $this->writeSpadEnables($last_spad_array);
                $ref_spad_count--;
                break;
            }

            $last_signal_rate_diff = $signal_rate_diff;
        }

        $this->ref_spad_count = $ref_spad_count;
        $this->ref_spad_type = $need_aperture_spads ? 1 : 0;
    }

    public function performRefCalibration(): void
    {
        $this->sendCommand([VL53L0XCommand::SYSTEM_SEQUENCE_CONFIG->value, 0x01]);
        $this->sendCommand([VL53L0XCommand::SYSRANGE_START->value, 0x01]);
        $this->sendCommand([VL53L0XCommand::SYSTEM_INTERRUPT_CLEAR->value, 0x01]);
        $this->sendCommand([VL53L0XCommand::SYSRANGE_START->value, 0x00]);

        $loop_count = 0;
        do {
            [$val] = $this->readData(VL53L0XCommand::RESULT_INTERRUPT_STATUS->value, 1);
            if (($val & 0x07) != 0) break;
            usleep(1000);
            $loop_count++;
        } while ($loop_count < 100);

        $this->sendCommand([VL53L0XCommand::SYSTEM_INTERRUPT_CLEAR->value, 0x01]);
        $this->sendCommand([VL53L0XCommand::SYSRANGE_START->value, 0x00]);

        [$this->vhv_settings] = $this->readData(VL53L0XCommand::VHV_CONFIG_PAD_SCL_SDA__EXTSUP_HV->value, 1);

        $this->sendCommand([VL53L0XCommand::SYSTEM_SEQUENCE_CONFIG->value, 0x02]);
        $this->sendCommand([VL53L0XCommand::SYSRANGE_START->value, 0x01]);
        $this->sendCommand([VL53L0XCommand::SYSTEM_INTERRUPT_CLEAR->value, 0x01]);
        $this->sendCommand([VL53L0XCommand::SYSRANGE_START->value, 0x00]);

        $loop_count = 0;
        do {
            [$val] = $this->readData(VL53L0XCommand::RESULT_INTERRUPT_STATUS->value, 1);
            if (($val & 0x07) != 0) break;
            usleep(1000);
            $loop_count++;
        } while ($loop_count < 100);

        $this->sendCommand([VL53L0XCommand::SYSTEM_INTERRUPT_CLEAR->value, 0x01]);
        $this->sendCommand([VL53L0XCommand::SYSRANGE_START->value, 0x00]);

        [$this->phase_cal] = $this->readData(VL53L0XCommand::ALGO_PHASECAL_LIM->value, 1);

        $this->sendCommand([VL53L0XCommand::SYSTEM_SEQUENCE_CONFIG->value, 0xE8]);
    }

    private function getNextGoodSpad(int $start_index): int
    {
        for ($i = $start_index; $i < 48; $i++) {
            $byte_index = (int)($i / 8);
            $bit_index = $i % 8;

            if (($this->nvm_ref_good_spad_map[$byte_index] >> $bit_index) & 0x1) {
                return $i;
            }
        }

        return -1;
    }

    private function enableSpadBit(array &$spad_array, int $spad_index): void
    {
        $byte_index = (int)($spad_index / 8);
        $bit_index = $spad_index % 8;
        $spad_array[$byte_index] |= (1 << $bit_index);
    }

    private function isApertureSpad(int $spad_index): bool
    {
        return $spad_index > 127;
    }

    private function writeSpadEnables(array $spad_array): void
    {
        $this->sendCommand([
            VL53L0XCommand::GLOBAL_CONFIG_SPAD_ENABLES_REF_0->value,
            $spad_array[0],
            $spad_array[1],
            $spad_array[2],
            $spad_array[3],
            $spad_array[4],
            $spad_array[5]
        ]);
    }

    private function performRefSignalMeasurement(): int
    {
        $this->sendCommand([VL53L0XCommand::SYSTEM_SEQUENCE_CONFIG->value, 0xC0]);
        $this->sendCommand([VL53L0XCommand::SYSRANGE_START->value, 0x01]);
        $this->sendCommand([VL53L0XCommand::SYSTEM_INTERRUPT_CLEAR->value, 0x01]);
        $this->sendCommand([VL53L0XCommand::SYSRANGE_START->value, 0x00]);

        $loop_count = 0;
        do {
            [$val] = $this->readData(VL53L0XCommand::RESULT_INTERRUPT_STATUS->value, 1);
            if (($val & 0x07) != 0) break;
            usleep(1000);
            $loop_count++;
        } while ($loop_count < 100);

        $this->sendCommand([VL53L0XCommand::SYSTEM_INTERRUPT_CLEAR->value, 0x01]);
        $this->sendCommand([VL53L0XCommand::SYSRANGE_START->value, 0x00]);

        [$low, $high] = $this->readData(VL53L0XCommand::RESULT_CORE_AMBIENT_WINDOW_EVENTS_RTN->value, 2);
        $peak_signal_rate = ($high << 8) | $low;

        $this->sendCommand([VL53L0XCommand::SYSTEM_SEQUENCE_CONFIG->value, 0xE8]);

        return $peak_signal_rate;
    }

    public function reset(): void
    {
        $this->xShutLow();
        $this->wait(2);
        $this->xShutHigh();
        $this->wait(2);
    }
}
