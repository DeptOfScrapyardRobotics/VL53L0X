<?php

namespace ScrapyardIO\Sensors\Distance\VL53L0X\Enums;

/**
 * VL53L0X Page 1 Register Map
 * 
 * These registers are accessed after writing 0xFF = 0x01
 * Switch back to page 0 with 0xFF = 0x00
 */
enum VL53L0XP1Command: int
{
    // Page Control
    case PAGE_SELECT = 0x00;
    
    // Phase Calibration
    case ALGO_PHASECAL_LIM = 0x30;
    
    // NVM Control and Status
    case NVM_CTRL_STATUS = 0x81;
    
    // NVM Access Control (Strobe/BIST Control) - same register used for both operations
    case NVM_BIST_CTRL = 0x83;
    case OSC_FREQUENCY = 0x84;
    
    // NVM Data Read Register
    case NVM_DATA_OUT = 0x90;
    
    // Internal Stop Variable
    case STOP_VARIABLE = 0x91;
    
    // NVM Address Select Register
    case NVM_READ_COMMAND = 0x94;
    
    // Result Peak Signal Rate Reference
    case RESULT_PEAK_SIGNAL_RATE_REF = 0xB6;
    
    // NVM Internal Addresses (values written to NVM_READ_COMMAND)
    case NVM_GOOD_SPAD_MAP_0 = 0x24;
    case NVM_GOOD_SPAD_MAP_4 = 0x25;
    case NVM_SPAD_INFO = 0x6B;
}
