<?php

namespace ScrapyardIO\Sensors\Distance\VL53L0X\Concerns;

use ScrapyardIO\Displays\Colors\Color;
use ScrapyardIO\Support\DataManipulation\ByteRegister;
use ScrapyardIO\Displays\Color\ST7796\Enums\ST7796Command;
use ScrapyardIO\Displays\Color\ST7796\Enums\ST7796ColorMode;
use ScrapyardIO\Transports\Concerns\XShutPin;
use ScrapyardIO\Transports\I2CTransport;

trait VL53L0XI2CChip
{
    use XShutPin;

    protected ?I2CTransport $vl53l0x_i2c = null;
    protected int $vl53l0x_i2c_bus = 1;
    protected int $vl53l0x_i2c_address = 0;
    protected int $max_packet_size = 1024;

    protected function i2c_vl53l0x_bus(?int $bus = null): int
    {
        if($bus)
        {
            $this->vl53l0x_i2c_bus = $bus;
        }
        return $this->vl53l0x_i2c_bus;
    }

    protected function i2c_vl53l0x_address(?int $address = null): int
    {
        if($address)
        {
            $this->vl53l0x_i2c_address = $address;
        }
        return $this->vl53l0x_i2c_address;
    }

    protected function vl53l0x_i2c(): ?I2CTransport
    {
        if(empty($this->vl53l0x_i2c))
        {
            $this->vl53l0x_i2c = new I2CTransport(
                $this->i2c_vl53l0x_address(),
                $this->i2c_vl53l0x_bus()
            );
        }

        return $this->vl53l0x_i2c;
    }

    public function readData(int $command, int $num_bytes_to_read): array
    {
        $this->sendCommand([$command]);
        return $this->vl53l0x_i2c()->read($num_bytes_to_read);
    }

    public function sendCommand(array $bytes): void
    {
        $this->vl53l0x_i2c()->notify($bytes);
    }
}
