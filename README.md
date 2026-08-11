# dept-of-scrapyard-robotics/vl53lxx (0.7)

I2C drivers for VL53L0X / VL53L1X time-of-flight rangefinders. Extends `GeneralPurposeIO\Circuits\SensorIC`.

## Register

Provider registers catalog slugs `vl53l0x` and `vl53l1x`.

```php
Circuit::ic('vl53l0x')->i2c(/* … */)->make();
Circuit::ic('vl53l1x')->i2c(/* … */)->make();
```

Protocol options: `I2C` alone, or `I2C+DigitalIO` when wiring optional XSHUT (`xshut_pin` + digital `driver`/`device` when the I2C bus cannot serve GPIO). XSHUT is strongly recommended for a clean boot (especially over MPSSE).

## Distance units

`Waveforms\Contracts\Sensors\Enums\DistanceUnit`. Both chips implement `Waveforms\Distance\MeasuresDistance` for `Rangefinder::circuit($profile)`. Raw ranging is millimetres; `distance()` converts via `DistanceUnit::convertFromMm()`.

## VL53L1X demos (soft)

Suggest `scrapyard-io/tubes` (+ `scrapyard-io/ux` for Scene HUD). When tubes is installed:

```bash
./runner vl53l1x-oled-demo
./runner vl53l1x-canvas-demo
./runner vl53l1x-canvas-demo --profile=vl53l1x
```

- No `--profile`: one `vl53l1x` circuits profile → use it; several → prompt; none → quit.
- Canvas demos use `tubes.defaults.canvas` (window **or** panel, e.g. `metal-canvas`). OLED requires `MonochromePanel`; canvas / UX reject mono only.
- With UX installed, `vl53l1x-canvas-demo` is replaced by the UX Scene sketch; alias `vl53l1x-ux-canvas-demo` also registers.
