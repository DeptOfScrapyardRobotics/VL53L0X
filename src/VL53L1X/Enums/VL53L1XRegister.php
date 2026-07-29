<?php

namespace DeptOfScrapyardRobotics\Sensors\VL53Lxx\VL53L1X\Enums;

enum VL53L1XRegister: int
{
    case SOFT_RESET = 0x0000;
    case I2C_SLAVE_DEVICE_ADDRESS = 0x0001;

    case VHV_CONFIG_TIMEOUT_MACROP_LOOP_BOUND = 0x0008;
    case VHV_CONFIG_INIT = 0x000B;

    // Start of the contiguous default configuration block written at boot.
    case CONFIG_START = 0x002D;

    case GPIO_HV_MUX_CTRL = 0x0030;
    case GPIO_TIO_HV_STATUS = 0x0031;

    case SYSTEM_INTERRUPT_CLEAR = 0x0086;
    case SYSTEM_MODE_START = 0x0087;

    case RESULT_RANGE_STATUS = 0x0089;
    case RESULT_FINAL_CROSSTALK_CORRECTED_RANGE_MM_SD0 = 0x0096;

    case FIRMWARE_SYSTEM_STATUS = 0x00E5;
    case IDENTIFICATION_MODEL_ID = 0x010F;
}
