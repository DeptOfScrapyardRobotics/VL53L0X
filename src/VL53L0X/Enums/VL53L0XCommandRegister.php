<?php

namespace DeptOfScrapyardRobotics\Sensors\VL53Lxx\VL53L0X\Enums;

enum VL53L0XCommandRegister: int
{
    // Identification
    case IDENTIFICATION_MODEL_ID = 0xC0;
    case IDENTIFICATION_REVISION_ID = 0xC2;

    // Result
    case RESULT_INTERRUPT_STATUS = 0x13;
    case RESULT_RANGE_STATUS = 0x14;
}
