<?php

namespace DeptOfScrapyardRobotics\Sensors\VL53Lxx\VL53L1X;

use DeptOfScrapyardRobotics\Sensors\VL53Lxx\VL53L1X\Concerns\VL53L1XAPI;
use DeptOfScrapyardRobotics\Sensors\VL53Lxx\VL53L1X\Enums\VL53L1XI2CAddress;
use DeptOfScrapyardRobotics\Sensors\VL53Lxx\VL53L1X\Exceptions\VL53L1XException;
use DeptOfScrapyardRobotics\Sensors\VL53Lxx\VL53LxxCarrierTransport;
use Exception;
use Fabricate\Contracts\Circuits\Attributes\IntegratedCircuit;
use Fabricate\Contracts\Circuits\IntegratedCircuit as CircuitContract;
use Fabricate\Contracts\NutsAndBolts\BootSequence;
use Fabricate\Contracts\Sensors\Enums\DistanceUnit;
use Fabricate\Contracts\Sensors\Interfaces\Rangefinder;
use GeneralPurposeIO\Digital\DigitalIO;
use GeneralPurposeIO\Digital\DigitalOutputPin;
use GeneralPurposeIO\I2C\I2C;
use GeneralPurposeIO\I2C\I2CSlave;

/**
 * VL53L1X single-zone ToF rangefinder.
 *
 * Constructor scope is intentionally slim versus VL53L0X: transport + optional
 * XSHUT (+ boot_now). The L1X firmware loads a fixed default configuration in
 * boot(); there are no L0X-style sequence/limit injectables in this driver.
 */
#[IntegratedCircuit('I2C')]
class VL53L1X implements CircuitContract, BootSequence, Rangefinder
{
    use VL53L1XAPI;

    /**
     * @throws Exception
     */
    public function __construct(
        protected readonly VL53LxxCarrierTransport $transport,
        protected ?DigitalOutputPin $xshut = null,
        bool $boot_now = false,
    ) {
        if ($boot_now) {
            $this->boot();
        }
    }

    /**
     * The device free-runs in continuous mode (started in boot()), so a read
     * waits for the next completed sample, takes it, and clears the interrupt
     * to release the following measurement.
     *
     * @throws VL53L1XException
     */
    public function readRange(): int
    {
        if (! $this->statusWait(2000)) {
            return -1;
        }

        $raw = $this->readStatus();
        $this->clearInterrupt();

        return $raw;
    }

    /**
     * @throws VL53L1XException
     */
    public function distance(DistanceUnit $unit): float
    {
        $mm = $this->readRange();

        return match ($unit) {
            DistanceUnit::CM => $mm / 10.0,
            DistanceUnit::M => $mm / 1000.0,
            DistanceUnit::IN => $mm / 25.4,
            DistanceUnit::FT => $mm / 304.8,
            DistanceUnit::YD => $mm / 914.4,
            DistanceUnit::uM => $mm * 1_000.0,
            DistanceUnit::nM => $mm * 1_000_000.0,
            default => (float) $mm,
        };
    }

    public function close(): void
    {
        // XSHUT often shares the MPSSE context with I2C — never mpsse_close via the pin.
        $this->transport->close();
    }

    /**
     * Build from adapter/device names. Resolve optional XSHUT from pin numbers
     * the same way SPI displays resolve DC/RST.
     *
     * @throws Exception
     */
    public static function i2c(
        string|int $device,
        ?string $adapter = null,
        int $slave = VL53L1XI2CAddress::DEFAULT->value,
        string|int|null $digital_device = null,
        ?string $digital_adapter = null,
        ?int $xshut_pin = null,
        bool $boot_now = true,
    ): static {
        $bus = I2C::adapter($adapter)
            ->device($device)
            ->bus();

        $xshut = null;
        if (! is_null($xshut_pin)) {
            $digitalBus = $bus;
            if (! $bus->canServeDigitalPins()) {
                if (is_null($digital_device)) {
                    throw VL53L1XException::digitalDeviceRequiredForXshut();
                }

                $digitalBus = DigitalIO::adapter($digital_adapter)
                    ->device($digital_device)
                    ->bus();
            }

            // Claim XSHUT first (defaults high), then hardware-reset pulse before I2C.
            $xshut = $digitalBus->output($xshut_pin);
            $xshut->low();
            usleep(10_000);
            if (! $xshut->high()) {
                throw VL53L1XException::xshutDriveFailed();
            }
            usleep(10_000);
        }

        $i2c = $bus->slave($slave);

        return static::fromI2CBus($i2c, $xshut, $boot_now);
    }

    /**
     * Build from an already-open I2C slave and optional ready-built XSHUT pin.
     *
     * @throws Exception
     */
    public static function fromI2CBus(
        I2CSlave $i2c,
        ?DigitalOutputPin $xshut = null,
        bool $boot_now = true,
    ): static {
        $transport = new VL53LxxCarrierTransport(i2c: $i2c);

        return new static($transport, $xshut, $boot_now);
    }
}
