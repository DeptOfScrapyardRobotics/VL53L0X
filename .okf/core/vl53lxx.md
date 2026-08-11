---
type: Module
title: VL53Lxx ICs
description: VL53L0X / VL53L1X SensorIC drivers — attributes, I2C+optional XSHUT factories, ranging API.
resource: src/
tags: [core, ic, sensor, i2c, tof, vl53l0x, vl53l1x]
generated: { by: cursor-agent/grok-4.5, at: "2026-08-11T02:00:00Z" }
verified: { by: null, at: null }
status: draft
sources:
  - id: l0x
    resource: src/VL53L0X/VL53L0X.php
    title: VL53L0X class
  - id: l1x
    resource: src/VL53L1X/VL53L1X.php
    title: VL53L1X class
  - id: l0x-api
    resource: src/VL53L0X/Concerns/VL53L0XAPI.php
    title: VL53L0XAPI (BootScaffolding)
  - id: l1x-api
    resource: src/VL53L1X/Concerns/VL53L1XAPI.php
    title: VL53L1XAPI (BootScaffolding)
  - id: transport
    resource: src/VL53LxxCarrierTransport.php
    title: VL53LxxCarrierTransport
  - id: distance-unit
    resource: vendor/scrapyard-io/waveforms (DistanceUnit)
    title: Waveforms DistanceUnit
  - id: l0x-addr
    resource: src/VL53L0X/Enums/VL53L0XI2CAddress.php
    title: VL53L0XI2CAddress
  - id: l1x-addr
    resource: src/VL53L1X/Enums/VL53L1XI2CAddress.php
    title: VL53L1XI2CAddress
---

# Role

I2C time-of-flight rangefinders for ST VL53L0X and VL53L1X. Each class extends `GeneralPurposeIO\Circuits\SensorIC` and implements `BootSequence` + `Waveforms\Distance\MeasuresDistance`.[^l0x][^l1x]

Keep chip methods (`readRange` / `distance`). App-facing wrapping is waveforms `Rangefinder` (capability inject), not a second chip contract here. VL53L5CX lives in sibling package `dept-of-scrapyard-robotics/vl53l5cx`.

# Attributes (both ICs)

```php
#[IntegratedCircuit('I2C', ['I2C', 'DigitalIO'])]
#[Pinout(
    ['I2C' => ['driver', 'device', 'slave']],
    ['I2C' => ['driver', 'device', 'slave'], 'DigitalIO' => ['driver', 'device', 'xshut']],
)]
```

Index-aligned protocol options: I2C alone, or I2C+DigitalIO when wiring optional XSHUT. See [XSHUT optional](../traps/xshut-optional.md).[^l0x][^l1x]

# Factories

| Factory | Notes |
|---------|-------|
| `::{i2c}(…)` | `I2C::adapter` → bus → optional XSHUT claim/pulse → `slave` → `fromI2CBus` |
| `::fromI2CBus(I2CSlave $i2c, …)` | Already-open slave + optional ready `DigitalOutputPin` |

Default `boot_now` is **true** on factories; constructor default is **false** (boot only when asked).[^l0x][^l1x]

**VL53L0X `i2c` extras** (sequence/limit injectables): `i2c_2v8_mode`, `final_range_enabled`, `pre_range_enabled`, `tcc_enabled`, `dss_enabled`, `msrc_enabled`, optional sigma / signal-ref / range-ignore thresholds.[^l0x]

**VL53L1X ctor is slim:** transport + optional XSHUT (+ `boot_now`). Firmware loads a fixed default configuration in `_boot()` — no L0X-style sequence injectables.[^l1x]

XSHUT factory args (both): `xshut_pin`, optional `digital_device` / `digital_adapter` when the I2C bus cannot serve digital pins (`canServeDigitalPins()`).

# Ranging surface

| Method | Meaning |
|--------|---------|
| `readRange(): int` | Raw millimetres (−1 on timeout / invalid L0X sample) |
| `distance(DistanceUnit $unit = MM): float` | Convert via waveforms `DistanceUnit::convertFromMm()` |
| `close(): void` | Closes **transport only** — never MPSSE-close via the XSHUT pin |

L0X: kick off measurement → `statusWait` → read → clear interrupt; clamps ≥ 8190 to −1.[^l0x]

L1X: free-runs continuous ranging (started in boot); read waits for next sample, takes it, clears interrupt.[^l1x]

# Boot

- Uses `GeneralPurposeIO\Contracts\Circuits\BootScaffolding` via per-chip `*API` traits.[^l0x-api][^l1x-api]
- `_boot()`: optional XSHUT `reset()` pulse → `dataInit()` → chip-specific static/default config.
- Wire path: `VL53LxxCarrierTransport` over `I2CSlave` (`write` / `writeRead`).[^transport]
- Register width: L0X **8-bit** addresses; L1X **16-bit** MSB-first.[^l0x-api][^l1x-api]

# Chip differences

| Concern | VL53L0X | VL53L1X |
|---------|---------|---------|
| Ctor injectables | Sequence/limit flags + optional thresholds | Transport + XSHUT only |
| Register address | 8-bit | 16-bit |
| Post-boot ranging | On-demand kickoff | Continuous free-run |
| Soft-reset | Part of L0X data path | Soft-reset + await FW after model-id check |

# Units

Both chips implement `Waveforms\Distance\MeasuresDistance`. Units are `Waveforms\Contracts\Sensors\Enums\DistanceUnit` (no package-local enum). Raw ranging is millimetres. See [Fabricate leftovers](../traps/fabricate-leftovers.md).

# Related

* [Circuits integration](circuits.md)
* [VL53L1X demo sketches](vl53l1x-demos.md)
* [Package (0.7)](../orientation/package.md)
* [XSHUT optional](../traps/xshut-optional.md)

[^l0x]: VL53L0X class
[^l1x]: VL53L1X class
[^l0x-api]: VL53L0XAPI (BootScaffolding)
[^l1x-api]: VL53L1XAPI (BootScaffolding)
[^transport]: VL53LxxCarrierTransport
[^l0x-addr]: VL53L0XI2CAddress
[^l1x-addr]: VL53L1XI2CAddress
