<?php

namespace DeptOfScrapyardRobotics\Sensors\VL53Lxx\Runners\Sketches\Demos\VL53L1X\Concerns;

use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Command\Command;
use Throwable;
use Waveforms\Distance\Rangefinder;

/**
 * Resolve a vl53l1x circuits.php profile and open {@see Rangefinder}.
 *
 * @mixin \Fabricate\Sketches\Sketch
 */
trait ResolvesVl53l1xCircuitProfile
{
    protected ?string $circuitProfile = null;

    protected ?Rangefinder $rangefinder = null;

    protected bool $stopRequested = false;

    protected function configureVl53l1xProfileOption(Command $command): void
    {
        $command->addOption(
            'profile',
            null,
            InputOption::VALUE_REQUIRED,
            'circuits.php profile name (ic must be vl53l1x)',
        );
    }

    protected function installStopHandlers(): void
    {
        if (! extension_loaded('pcntl')) {
            return;
        }

        pcntl_async_signals(true);
        $stop = function (): void {
            $this->stopRequested = true;
        };
        pcntl_signal(SIGINT, $stop);
        pcntl_signal(SIGTERM, $stop);
    }

    /**
     * @return bool false when the sketch should quit (errors already printed)
     */
    protected function bootVl53l1xRangefinder(): bool
    {
        $profiles = $this->vl53l1xProfiles();

        if ($profiles === []) {
            $this->error('No vl53l1x profiles in config/circuits.php. Add a profile with ic => vl53l1x.');

            return false;
        }

        $requested = $this->option('profile');
        if (is_string($requested) && $requested !== '') {
            if (! isset($profiles[$requested])) {
                $this->error("Profile [{$requested}] is missing or not a vl53l1x ic.");

                return false;
            }
            $this->circuitProfile = $requested;
        } elseif (count($profiles) === 1) {
            $this->circuitProfile = array_key_first($profiles);
        } else {
            $this->circuitProfile = $this->choice('Which vl53l1x profile?', array_keys($profiles));
        }

        try {
            $this->rangefinder = Rangefinder::circuit($this->circuitProfile);
            $this->syncRangefinderScale();
        } catch (Throwable $e) {
            $this->error($e->getMessage());
            $this->rangefinder = null;

            return false;
        }

        return true;
    }

    /**
     * Pull chip MinDistance / MaxDistance props into the sketch bar scale.
     */
    protected function syncRangefinderScale(): void
    {
        if (is_null($this->rangefinder)) {
            return;
        }

        $range = $this->rangefinder->distanceRange();
        if (property_exists($this, 'rangeMinMm')) {
            $this->rangeMinMm = (int) round($range['min']);
        }
        if (property_exists($this, 'rangeMaxMm')) {
            $this->rangeMaxMm = max(
                (property_exists($this, 'rangeMinMm') ? $this->rangeMinMm : 0) + 1,
                (int) round($range['max']),
            );
        }
    }

    protected function closeVl53l1xRangefinder(): void
    {
        // Rangefinder holds the IC; closing the underlying circuit is via Circuit profile lifecycle.
        $this->rangefinder = null;
        $this->circuitProfile = null;
    }

    /**
     * @return array<string, array<string, mixed>>
     */
    protected function vl53l1xProfiles(): array
    {
        $all = config('circuits', []);
        if (! is_array($all)) {
            return [];
        }

        $matched = [];
        foreach ($all as $name => $recipe) {
            if (! is_string($name) || ! is_array($recipe)) {
                continue;
            }
            $ic = $recipe['ic'] ?? null;
            if ($ic === 'vl53l1x') {
                $matched[$name] = $recipe;
            }
        }

        return $matched;
    }
}
