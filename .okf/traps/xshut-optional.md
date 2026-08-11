---
type: Trap
title: XSHUT optional
description: Optional DigitalIO protocol option for XSHUT; never close a shared MPSSE context via the pin.
tags: [traps, xshut, digitalio, mpsse, i2c]
generated: { by: cursor-agent/grok-4.5, at: "2026-08-11T02:00:00Z" }
verified: { by: null, at: null }
status: draft
sources:
  - id: l0x
    resource: src/VL53L0X/VL53L0X.php
    title: VL53L0X i2c factory + close
  - id: l1x
    resource: src/VL53L1X/VL53L1X.php
    title: VL53L1X i2c factory + close
  - id: l0x-api
    resource: src/VL53L0X/Concerns/VL53L0XAPI.php
    title: VL53L0XAPI reset
  - id: l1x-api
    resource: src/VL53L1X/Concerns/VL53L1XAPI.php
    title: VL53L1XAPI reset
---

# Trap

XSHUT is **optional** in the Circuits pinout (`I2C` alone, or `I2C`+`DigitalIO`). Do not require it in attributes or invent a mandatory interrupt pin for these ICs.

Still strongly recommended for a clean boot (especially over MPSSE): without a hardware reset pulse, bus state / prior address conflicts can leave the part wedged.

# Correct factory behavior

When `xshut_pin` is set:[^l0x][^l1x]

1. Prefer digital pins from the same I2C bus when `canServeDigitalPins()` is true (MPSSE shared context).
2. Otherwise require `digital_device` (+ optional `digital_adapter`) and open a separate `DigitalIO` bus — throw `*Exception::digitalDeviceRequiredForXshut()` if missing.
3. Claim XSHUT as an output, pulse **low → high** (active-low shutdown) **before** opening the I2C slave for boot.

When XSHUT is null, `reset()` in the API trait is a no-op — boot continues over I2C only.[^l0x-api][^l1x-api]

# Close rules (MPSSE)

`close()` must close **`$this->transport` only**. XSHUT often shares the MPSSE context with I2C — never `mpsse_close` (or equivalent) via the pin, or you tear down the still-needed I2C handle.[^l0x][^l1x]

# Related

* [VL53Lxx ICs](../core/vl53lxx.md)
* [Fabricate leftovers](fabricate-leftovers.md)

[^l0x]: VL53L0X i2c factory + close
[^l1x]: VL53L1X i2c factory + close
[^l0x-api]: VL53L0XAPI reset
[^l1x-api]: VL53L1XAPI reset
