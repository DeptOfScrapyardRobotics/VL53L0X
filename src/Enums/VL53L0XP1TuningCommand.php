<?php

namespace ScrapyardIO\Sensors\Distance\VL53L0X\Enums;

/**
 * VL53L0X Page 1 Tuning Register Map
 * 
 * These are undocumented internal tuning registers on Page 1
 * Accessed after writing PAGE_SELECT (0xFF) = 0x01
 */
enum VL53L0XP1TuningCommand: int
{
    case CONTROL_REGISTER = 0x00;
    case P1_TUNING_1 = 0x0D;
    case P1_TUNING_2 = 0x0E;
    case P1_TUNING_3 = 0x20;
    case P1_TUNING_4 = 0x22;
    case P1_TUNING_5 = 0x23;
    case P1_ALGO_PHASECAL_LIM = 0x30;
    case P1_TUNING_6 = 0x31;
    case P1_TUNING_7 = 0x40;
    case P1_TUNING_8 = 0x42;
    case P1_TUNING_9 = 0x43;
    case P1_TUNING_10 = 0x44;
    case P1_TUNING_11 = 0x45;
    case P1_TUNING_12 = 0x46;
    case P1_TUNING_13 = 0x47;
    case P1_TUNING_14 = 0x48;
    case P1_TUNING_15 = 0x49;
    case P1_TUNING_16 = 0x4A;
    case P1_TUNING_17 = 0x4B;
    case P1_TUNING_18 = 0x4C;
    case P1_TUNING_19 = 0x4D;
    case P1_TUNING_20 = 0x4E;
    case P1_TUNING_21 = 0x8E;
}
