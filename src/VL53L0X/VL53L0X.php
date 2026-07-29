<?php

namespace DeptOfScrapyardRobotics\Sensors\VL53Lxx\VL53L0X;

use DeptOfScrapyardRobotics\Sensors\VL53Lxx\VL53L0X\Concerns\VL53L0XAPI;
use DeptOfScrapyardRobotics\Sensors\VL53Lxx\VL53L0X\Enums\VL53L0XI2CAddress;
use DeptOfScrapyardRobotics\Sensors\VL53Lxx\VL53L0X\Exceptions\VL53L0XException;
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

#[IntegratedCircuit('I2C')]
class VL53L0X implements CircuitContract, BootSequence, Rangefinder
{
    use VL53L0XAPI;

    /**
     * @throws Exception
     */
    public function __construct(
        protected readonly VL53LxxCarrierTransport $transport,
        protected bool $i2c_2v8_mode,
        protected bool $final_range_enabled,
        protected bool $pre_range_enabled,
        protected bool $tcc_enabled,
        protected bool $dss_enabled,
        protected bool $msrc_enabled,
        protected ?int $sigma_limit,
        protected ?int $signal_ref_clip,
        protected ?int $range_ignore_threshold,
        protected ?DigitalOutputPin $xshut = null,
        bool $boot_now = false,
    ) {
        if ($boot_now) {
            $this->boot();
        }
    }

    /**
     * @throws VL53L0XException
     */
    public function readRange(): int
    {
        $this->kickOffMeasurement();
        $ready = $this->statusWait();

        if (! $ready) {
            return -1;
        }

        $raw = $this->readStatus();
        $this->clearInterrupt();

        return $raw < 8190 ? $raw : -1;
    }

    /**
     * @throws VL53L0XException
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
        int $slave = VL53L0XI2CAddress::DEFAULT->value,
        bool $i2c_2v8_mode = true,
        bool $final_range_enabled = true,
        bool $pre_range_enabled = true,
        bool $tcc_enabled = true,
        bool $dss_enabled = true,
        bool $msrc_enabled = true,
        ?int $sigma_limit = null,
        ?int $signal_ref_clip = null,
        ?int $range_ignore_threshold = null,
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
                    throw VL53L0XException::digitalDeviceRequiredForXshut();
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
                throw VL53L0XException::xshutDriveFailed();
            }
            usleep(10_000);
        }

        $i2c = $bus->slave($slave);

        return static::fromI2CBus(
            $i2c,
            $i2c_2v8_mode,
            $final_range_enabled,
            $pre_range_enabled,
            $tcc_enabled,
            $dss_enabled,
            $msrc_enabled,
            $sigma_limit,
            $signal_ref_clip,
            $range_ignore_threshold,
            $xshut,
            $boot_now,
        );
    }

    /**
     * Build from an already-open I2C slave and optional ready-built XSHUT pin.
     *
     * @throws Exception
     */
    public static function fromI2CBus(
        I2CSlave $i2c,
        bool $i2c_2v8_mode = true,
        bool $final_range_enabled = true,
        bool $pre_range_enabled = true,
        bool $tcc_enabled = true,
        bool $dss_enabled = true,
        bool $msrc_enabled = true,
        ?int $sigma_limit = null,
        ?int $signal_ref_clip = null,
        ?int $range_ignore_threshold = null,
        ?DigitalOutputPin $xshut = null,
        bool $boot_now = true,
    ): static {
        $transport = new VL53LxxCarrierTransport(i2c: $i2c);

        return new static(
            $transport,
            $i2c_2v8_mode,
            $final_range_enabled,
            $pre_range_enabled,
            $tcc_enabled,
            $dss_enabled,
            $msrc_enabled,
            $sigma_limit,
            $signal_ref_clip,
            $range_ignore_threshold,
            $xshut,
            $boot_now,
        );
    }
}
