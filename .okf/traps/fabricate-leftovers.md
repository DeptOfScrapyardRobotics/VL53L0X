---
type: Trap
title: Fabricate leftovers
description: VL53Lxx 0.7 uses GeneralPurposeIO Circuits + waveforms MeasuresDistance — not Fabricate Circuits or local DistanceUnit.
tags: [traps, fabricate, circuits, sensors, waveforms, rangefinder]
generated: { by: cursor-agent/grok-4.5, at: "2026-08-11T16:20:00Z" }
verified: { by: null, at: null }
status: draft
sources:
  - id: l0x
    resource: src/VL53L0X/VL53L0X.php
    title: VL53L0X imports
  - id: l1x
    resource: src/VL53L1X/VL53L1X.php
    title: VL53L1X imports
  - id: provider
    resource: src/Providers/VL53LxxServiceProvider.php
    title: Circuit MagicAlias import
---

# Trap

Do **not** import or revive:

- `Fabricate\Contracts\Circuits\*`
- `Fabricate\Circuits\*` (including any old Fabricate boot / DataRegister paths)
- Package-local `DeptOfScrapyardRobotics\Sensors\VL53Lxx\Enums\DistanceUnit` (removed)

# Use instead

| Concern | Correct FQCN |
|---------|----------------|
| Taxonomy base | `GeneralPurposeIO\Circuits\Types\SensorIC` |
| Attributes / BootSequence / BootScaffolding | `GeneralPurposeIO\Contracts\Circuits\Attributes\*`, `BootSequence`, `BootScaffolding` |
| Circuit alias | `GeneralPurposeIO\Core\MagicAliases\Circuit` |
| Rangefinder qualify | `Waveforms\Distance\MeasuresDistance` |
| Units | `Waveforms\Contracts\Sensors\Enums\DistanceUnit` |
| Exceptions | Chip `*Exception` extending `GeneralPurposeIO\Contracts\Circuits\CircuitException` (plus package `VL53LxxException` for transport) |

Both `VL53L0X` and `VL53L1X` implement `MeasuresDistance` so `Rangefinder::circuit($profile)` accepts either chip.[^l0x][^l1x]

Raw `readRange()` stays millimetres; `distance()` converts via waveforms `DistanceUnit::convertFromMm()`.

This package depends on `scrapyard-io/waveforms` (not tubes).

# Related

* [VL53Lxx ICs](../core/vl53lxx.md)
* [Circuits integration](../core/circuits.md)
* [XSHUT optional](xshut-optional.md)

[^l0x]: VL53L0X imports
[^l1x]: VL53L1X imports
[^provider]: Circuit MagicAlias import
