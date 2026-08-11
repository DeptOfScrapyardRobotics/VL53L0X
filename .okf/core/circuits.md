---
type: Module
title: Circuits integration
description: Catalog registration for vl53l0x / vl53l1x via VL53LxxServiceProvider.
resource: src/Providers/VL53LxxServiceProvider.php
tags: [circuits, catalog, provider]
generated: { by: cursor-agent/grok-4.5, at: "2026-08-11T02:00:00Z" }
verified: { by: null, at: null }
status: draft
sources:
  - id: provider
    resource: src/Providers/VL53LxxServiceProvider.php
    title: VL53LxxServiceProvider
---

# Role

This package **owns the VL53L0X / VL53L1X chip drivers** and registers them with gpio-framework Circuits. Registry / fluent / profile **semantics** live in `scrapyard-io/gpio-framework` — open that package’s `.okf` for `CircuitRegistry`, `PendingCircuit`, and `circuit:make-profile` behavior.

# Catalog

On `boot()`:[^provider]

```php
Circuit::addCircuit('vl53l0x', VL53L0X::class);
Circuit::addCircuit('vl53l1x', VL53L1X::class);
```

Provider lives under `src/Providers/` (not package root). No package-local make-profile command or smoke sketch in this package today — use gpio-framework `circuit:make-profile` / `Circuit::ic(…)` as needed.

```php
Circuit::ic('vl53l0x')->i2c(/* … */)->make();
Circuit::ic('vl53l1x')->i2c(/* … */)->make();
```

# Related

* [VL53Lxx ICs](vl53lxx.md)
* [Package (0.7)](../orientation/package.md)

[^provider]: VL53LxxServiceProvider
