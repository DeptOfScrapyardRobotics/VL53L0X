<?php

namespace DeptOfScrapyardRobotics\Sensors\VL53Lxx\VL53L1X;

use DeptOfScrapyardRobotics\Sensors\VL53Lxx\VL53L1X\Concerns\VL53L1XAPI;
use DeptOfScrapyardRobotics\Sensors\VL53Lxx\VL53L1X\Enums\VL53L1XI2CAddress;
use DeptOfScrapyardRobotics\Sensors\VL53Lxx\VL53L1X\Exceptions\VL53L1XException;
use DeptOfScrapyardRobotics\Sensors\VL53Lxx\VL53LxxCarrierTransport;
use Exception;
use GeneralPurposeIO\Circuits\Types\SensorIC;
use GeneralPurposeIO\Contracts\Circuits\Attributes\IntegratedCircuit;
use GeneralPurposeIO\Contracts\Circuits\Attributes\Pinout;
use GeneralPurposeIO\Contracts\Circuits\BootSequence;
use GeneralPurposeIO\Digital\DigitalIO;
use GeneralPurposeIO\Digital\DigitalOutputPin;
use GeneralPurposeIO\I2C\I2C;
use GeneralPurposeIO\I2C\I2CSlave;
use Waveforms\Contracts\Distance\DistanceUnit;
use Waveforms\Contracts\Distance\MaxDistance;
use Waveforms\Contracts\Distance\MeasuresDistance;
use Waveforms\Contracts\Distance\MinDistance;

/**
 * VL53L1X single-zone ToF rangefinder.
 *
 * Constructor scope is intentionally slim versus VL53L0X: transport + optional
 * XSHUT (+ boot_now). The L1X firmware loads a fixed default configuration in
 * boot(); there are no L0X-style sequence/limit injectables in this driver.
 */
#[IntegratedCircuit('I2C', ['I2C', 'DigitalIO'])]
#[Pinout(
    ['I2C' => ['driver', 'device', 'slave']],
    ['I2C' => ['driver', 'device', 'slave'], 'DigitalIO' => ['driver', 'device', 'xshut']],
)]
class VL53L1X extends SensorIC implements BootSequence, MeasuresDistance
{
    use VL53L1XAPI;

    #[MaxDistance] protected $max_distance = 4000;
    #[MinDistance] protected $min_distance = 40;

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
    public function distance(DistanceUnit $unit = DistanceUnit::MM): float
    {
        return $unit->convertFromMm((float) $this->readRange());
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
