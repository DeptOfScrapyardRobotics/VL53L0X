<?php

namespace DeptOfScrapyardRobotics\Sensors\VL53Lxx\VL53L0X\Enums;

/**
 * VL53L0X Page 0 undocumented internal tuning registers (DefaultTuningSettings).
 */
enum VL53L0XTuningCommandRegister: int
{
    case INTERNAL_TUNING_1 = 0x10;
    case INTERNAL_TUNING_2 = 0x11;
    case INTERNAL_TUNING_3 = 0x22;
    case INTERNAL_TUNING_4 = 0x23;
    case INTERNAL_TUNING_5 = 0x24;
    case INTERNAL_TUNING_6 = 0x25;
    case INTERNAL_TUNING_7 = 0x31;
    case INTERNAL_TUNING_8 = 0x34;
    case INTERNAL_TUNING_9 = 0x35;
    case INTERNAL_TUNING_10 = 0x40;
    case INTERNAL_TUNING_11 = 0x42;
    case INTERNAL_TUNING_12 = 0x43;
    case INTERNAL_TUNING_13 = 0x45;
    case INTERNAL_TUNING_14 = 0x49;
    case INTERNAL_TUNING_15 = 0x4A;
    case INTERNAL_TUNING_16 = 0x4B;
    case INTERNAL_TUNING_17 = 0x4C;
    case INTERNAL_TUNING_18 = 0x4D;
    case INTERNAL_TUNING_19 = 0x54;
    case INTERNAL_TUNING_20 = 0x75;
    case INTERNAL_TUNING_21 = 0x76;
    case INTERNAL_TUNING_22 = 0x77;
    case INTERNAL_TUNING_23 = 0x78;
    case INTERNAL_TUNING_24 = 0x7A;
    case INTERNAL_TUNING_25 = 0x7B;
    case INTERNAL_TUNING_26 = 0x8E;
    case INTERNAL_TUNING_27 = 0x0D;
    case INTERNAL_TUNING_28 = 0x0E;
    case INTERNAL_TUNING_29 = 0x65;
    case INTERNAL_TUNING_30 = 0x66;
}
