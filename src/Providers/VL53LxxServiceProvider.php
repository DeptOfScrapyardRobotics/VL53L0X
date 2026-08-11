<?php

namespace DeptOfScrapyardRobotics\Sensors\VL53Lxx\Providers;

use DeptOfScrapyardRobotics\Sensors\VL53Lxx\Enums\Vl53l1xDemoSketch;
use DeptOfScrapyardRobotics\Sensors\VL53Lxx\Runners\Sketches\Demos\VL53L1X\CanvasTestSketch;
use DeptOfScrapyardRobotics\Sensors\VL53Lxx\Runners\Sketches\Demos\VL53L1X\OLEDTestSketch;
use DeptOfScrapyardRobotics\Sensors\VL53Lxx\Runners\Sketches\Demos\VL53L1X\UXCanvasTestSketch;
use DeptOfScrapyardRobotics\Sensors\VL53Lxx\VL53L0X\VL53L0X;
use DeptOfScrapyardRobotics\Sensors\VL53Lxx\VL53L1X\VL53L1X;
use Fabricate\Contracts\Sketches\SketchRegistry;
use Fabricate\NutsAndBolts\ServiceProvider;
use GeneralPurposeIO\Core\MagicAliases\Circuit;

class VL53LxxServiceProvider extends ServiceProvider
{
    public function register(): void {}

    public function boot(): void
    {
        Circuit::addCircuit('vl53l0x', VL53L0X::class);
        Circuit::addCircuit('vl53l1x', VL53L1X::class);

        $this->registerDemoSketches();
    }

    protected function registerDemoSketches(): void
    {
        if (! $this->container->bound(SketchRegistry::class)) {
            return;
        }

        // Tubes panel demos — soft dependency.
        if (! class_exists(\ScrapyardIO\Tubes\Core\MagicAliases\Panel::class)) {
            return;
        }

        /** @var SketchRegistry $registry */
        $registry = $this->container->make(SketchRegistry::class);

        if (! $registry->has(Vl53l1xDemoSketch::OLED->value)) {
            $registry->registerConvention(Vl53l1xDemoSketch::OLED->value, OLEDTestSketch::class);
        }

        if (! $registry->has(Vl53l1xDemoSketch::CANVAS->value)) {
            $registry->registerConvention(Vl53l1xDemoSketch::CANVAS->value, CanvasTestSketch::class);
        }

        // UX binds over the shared canvas slug when scrapyard-io/ux is installed.
        if (class_exists(\ScrapyardIO\UX\Core\Scene::class)) {
            $registry->replace(UXCanvasTestSketch::class);

            if (! $registry->has(Vl53l1xDemoSketch::UX_ALIAS->value)) {
                $registry->registerConvention(
                    Vl53l1xDemoSketch::UX_ALIAS->value,
                    UXCanvasTestSketch::class,
                );
            }
        }
    }
}
