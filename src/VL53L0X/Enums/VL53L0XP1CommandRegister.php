<?php

namespace DeptOfScrapyardRobotics\Sensors\VL53Lxx\VL53L0X\Enums;

/**
 * VL53L0X Page 1 register map.
 * Switch to page 1 with: PAGE_SELECT (0xFF) = 0x01
 * Switch back to page 0 with: PAGE_SELECT (0xFF) = 0x00
 */
enum VL53L0XP1CommandRegister: int
{
    case PAGE_SELECT = 0x00;
    case ALGO_PHASECAL_LIM = 0x30;
    case NVM_CTRL_STATUS = 0x81;
    case NVM_BIST_CTRL = 0x83;
    case OSC_FREQUENCY = 0x84;
    case NVM_DATA_OUT = 0x90;
    case STOP_VARIABLE = 0x91;
    case NVM_READ_COMMAND = 0x94;
    case RESULT_PEAK_SIGNAL_RATE_REF = 0xB6;

    // NVM address values written to NVM_READ_COMMAND
    case NVM_GOOD_SPAD_MAP_0 = 0x24;
    case NVM_GOOD_SPAD_MAP_4 = 0x25;
    case NVM_SPAD_INFO = 0x6B;
}
