<?php

namespace DeptOfScrapyardRobotics\Sensors\VL53Lxx\Runners\Sketches\Demos\VL53L1X;

use DeptOfScrapyardRobotics\Sensors\VL53Lxx\Runners\Sketches\Demos\VL53L1X\Concerns\OpensDefaultTubesCanvas;
use DeptOfScrapyardRobotics\Sensors\VL53Lxx\Runners\Sketches\Demos\VL53L1X\Concerns\PaintsTubesDistanceHud;
use DeptOfScrapyardRobotics\Sensors\VL53Lxx\Runners\Sketches\Demos\VL53L1X\Concerns\ResolvesVl53l1xCircuitProfile;
use Fabricate\Contracts\Sketches\Attributes\Sketch as SketchAttribute;
use Fabricate\Contracts\Sketches\SketchLoopResult;
use Fabricate\Sketches\Sketch;
use ScrapyardIO\Tubes\Panels\MonochromePanel;
use Symfony\Component\Console\Command\Command;
use Throwable;

/**
 * VL53L1X rangefinder on a MonochromePanel (SSD1306 / SH1106).
 *
 *   ./runner vl53l1x-oled-demo
 *   ./runner vl53l1x-oled-demo --profile=vl53l1x
 *
 * Requires tubes.defaults.canvas → a MonochromePanel.
 */
#[SketchAttribute('vl53l1x-oled-demo')]
class OLEDTestSketch extends Sketch
{
    use ResolvesVl53l1xCircuitProfile;
    use OpensDefaultTubesCanvas;
    use PaintsTubesDistanceHud;

    protected string $description = 'VL53L1X distance + bar on a MonochromePanel (Ctrl-C to stop)';

    protected bool $announced = false;

    protected int $lastSampleNs = 0;

    public function configureCommand(Command $command): void
    {
        $this->configureVl53l1xProfileOption($command);
    }

    public function boot(): void
    {
        $this->installStopHandlers();

        if (! $this->bootVl53l1xRangefinder()) {
            return;
        }

        if (! $this->bootDefaultTubesCanvas()) {
            return;
        }

        if (! $this->canvas instanceof MonochromePanel) {
            $kind = $this->canvas::class;
            $this->error(
                "OLED demo requires a MonochromePanel; tubes.defaults.canvas [{$this->canvasProfile}] opened {$kind}."
            );
            $this->closeDefaultTubesCanvas();
            $this->rangefinder = null;

            return;
        }
    }

    public function loop(): SketchLoopResult
    {
        if ($this->stopRequested || $this->defaultCanvasShouldStop()) {
            $this->info('VL53L1X OLED demo stopped.');

            return SketchLoopResult::STOP;
        }

        if (is_null($this->rangefinder) || is_null($this->canvas) || ! $this->canvas instanceof MonochromePanel) {
            return SketchLoopResult::STOP;
        }

        if (! $this->announced) {
            $this->info(
                "VL53L1X OLED via Rangefinder::circuit('{$this->circuitProfile}') → canvas [{$this->canvasProfile}]"
            );
            $this->line('  Distance mm + bar — Ctrl-C to end.');
            $this->announced = true;
        }

        $now = hrtime(true);
        if ($this->lastSampleNs !== 0 && ($now - $this->lastSampleNs) < 300_000_000) {
            usleep(5_000);

            return SketchLoopResult::CONTINUE;
        }

        try {
            $mm = (int) round($this->rangefinder->distance());
            $renderer = $this->canvasRenderer();
            $this->paintDistanceHud($renderer, $this->canvas, $mm);
            $this->canvas->present();
        } catch (Throwable $e) {
            $this->error($e->getMessage());

            return SketchLoopResult::STOP;
        }

        $this->lastSampleNs = $now;

        return SketchLoopResult::CONTINUE;
    }

    public function shutdown(): void
    {
        $this->closeDefaultTubesCanvas();
        $this->closeVl53l1xRangefinder();
    }
}
