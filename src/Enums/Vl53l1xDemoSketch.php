<?php

namespace DeptOfScrapyardRobotics\Sensors\VL53Lxx\Enums;

/**
 * Workshop sketch slugs for VL53L1X rangefinder demos.
 */
enum Vl53l1xDemoSketch: string
{
    case OLED = 'vl53l1x-oled-demo';
    case CANVAS = 'vl53l1x-canvas-demo';
    case UX_ALIAS = 'vl53l1x-ux-canvas-demo';
}
