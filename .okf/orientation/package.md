---
type: Module
title: Package (0.7)
description: dept-of-scrapyard-robotics/vl53lxx Composer identity, namespace, and discovery.
resource: composer.json
tags: [orientation, package, 0.7, vl53lxx, vl53l0x, vl53l1x]
generated: { by: cursor-agent/grok-4.5, at: "2026-08-11T02:00:00Z" }
verified: { by: null, at: null }
status: draft
sources:
  - id: composer
    resource: composer.json
    title: Package composer.json
  - id: provider
    resource: src/Providers/VL53LxxServiceProvider.php
    title: VL53LxxServiceProvider
  - id: gitattributes
    resource: .gitattributes
    title: Dist export-ignore
---

# Identity

| Field | Value |
|-------|-------|
| Composer | `dept-of-scrapyard-robotics/vl53lxx` **0.7.0** |
| PHP | `^8.4\|^8.5\|^8.6` |
| Namespace | `DeptOfScrapyardRobotics\Sensors\VL53Lxx\` → `src/` |
| Provider | `DeptOfScrapyardRobotics\Sensors\VL53Lxx\Providers\VL53LxxServiceProvider` (`Providers/` folder) |
| Catalog slugs | `vl53l0x`, `vl53l1x` |
| Branch alias | `dev-master` → `0.7.x-dev` |

# Requires

| Package | Constraint |
|---------|------------|
| `scrapyard-io/gpio-framework` | `^0.7.0` |
| `scrapyard-io/waveforms` | `^0.7.0` |

**No hard** `scrapyard-io/tubes` / `scrapyard-io/ux` — sensor package first; demos are soft.[^composer]

Suggested (optional): `microscrap/i2c`, `microscrap/mpsse`, `scrapyard-io/tubes`, `scrapyard-io/ux` at `^0.7.0`.[^composer]

# Discovery

`extra.scrapyard-io.providers` lists `Providers\VL53LxxServiceProvider`. That provider registers the two catalog ICs on `boot()`, and (when tubes is present) the VL53L1X demo sketches — UX replaces the shared canvas slug when installed. See [VL53L1X demo sketches](../core/vl53l1x-demos.md).[^provider]

# Dist

`.okf/` and `AGENTS.md` are `export-ignore` — Composer dist tarballs omit them.[^gitattributes]

# Related

* [VL53Lxx ICs](../core/vl53lxx.md)
* [Circuits integration](../core/circuits.md)
* [VL53L1X demo sketches](../core/vl53l1x-demos.md)

[^composer]: Package composer.json
[^provider]: VL53LxxServiceProvider
[^gitattributes]: Dist export-ignore
