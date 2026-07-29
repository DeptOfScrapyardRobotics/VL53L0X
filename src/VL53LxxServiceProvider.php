<?php

namespace DeptOfScrapyardRobotics\Sensors\VL53Lxx;

use DeptOfScrapyardRobotics\Sensors\VL53Lxx\VL53L0X\VL53L0X;
use DeptOfScrapyardRobotics\Sensors\VL53Lxx\VL53L1X\VL53L1X;
use Fabricate\NutsAndBolts\MagicAliases\Circuit;
use Fabricate\NutsAndBolts\ServiceProvider;

class VL53LxxServiceProvider extends ServiceProvider
{
    public function register(): void {}

    public function boot(): void
    {
        Circuit::addCircuit('vl53l0x', VL53L0X::class);
        Circuit::addCircuit('vl53l1x', VL53L1X::class);
    }
}
