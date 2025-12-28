<?php

namespace ScrapyardIO\Sensors\Distance\VL53L0X\Adapters;

use ScrapyardIO\Sensors\Enums\SensorType;
use ScrapyardIO\Support\Attributes\Sensor;
use ScrapyardIO\Sensors\Distance\Adapters\DistanceSensorAdapter;
use ScrapyardIO\Sensors\Distance\VL53L0X\Concerns\VL53L0XI2CChip;
use ScrapyardIO\Sensors\Distance\VL53L0X\Enums\VL53L0XI2CAddress;
use ScrapyardIO\Sensors\Distance\VL53L0X\Concerns\VL53L0XBootSequence;
use ScrapyardIO\Sensors\Distance\VL53L0X\Exceptions\VL53L0XException;

#[Sensor('VL53L0X', VL53L0XI2CAddress::FIXED->value, SensorType::PROXIMITY)]
class VL53L0XI2CAdapter extends DistanceSensorAdapter
{
    use VL53L0XI2CChip;
    use VL53L0XBootSequence;

    public function bus(int $bus):static
    {
        $this->i2c_vl53l0x_bus($bus);
        return $this;
    }

    public function address(VL53L0XI2CAddress $address):static
    {
        $this->i2c_vl53l0x_address($address->value);
        return $this;
    }

    public function xShutPin(int $chip, int $line): static
    {
        $this->x_shut_chip($chip);
        $this->x_shut_line($line);
        $this->x_shut_gpio();

        return $this;
    }

    public function rawMillimeters(): int
    {
        $results = -1;
        $this->kickOffMeasurement();
        $ready = $this->statusWait();

        if($ready)
        {
            $raw = $this->readStatus();
            $this->clearInterrupt();
            if($raw < 8190) $results = $raw;
        }

        return $results;
    }


    /**
     * @return $this
     * @throws VL53L0XException
     */
    public function boot(): static
    {
        $this->vl53l0x_i2c();

        $this->reset();
        $this->dataInit();
        $this->staticInit();
        $this->performRefSpadManagement();
        $this->performRefCalibration();
        $this->setDeviceMode();

        return $this;
    }
}
