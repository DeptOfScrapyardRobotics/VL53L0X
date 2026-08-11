---
type: Module
title: VL53L1X demo sketches
description: Soft tubes/ux runner demos — profile resolution, mono gate only, UX bind-over of the shared canvas slug.
resource: src/Runners/Sketches/Demos/VL53L1X/
tags: [core, demos, sketches, tubes, ux, vl53l1x, rangefinder]
generated: { by: cursor-agent/grok-4.5, at: "2026-08-11T16:50:00Z" }
verified: { by: null, at: null }
status: draft
sources:
  - id: provider
    resource: src/Providers/VL53LxxServiceProvider.php
    title: Soft sketch registration
  - id: enum
    resource: src/Enums/Vl53l1xDemoSketch.php
    title: Demo slug enum
  - id: profile
    resource: src/Runners/Sketches/Demos/VL53L1X/Concerns/ResolvesVl53l1xCircuitProfile.php
    title: Profile resolution
  - id: canvas-open
    resource: src/Runners/Sketches/Demos/VL53L1X/Concerns/OpensDefaultTubesCanvas.php
    title: Default tubes canvas open (window or panel)
  - id: oled
    resource: src/Runners/Sketches/Demos/VL53L1X/OLEDTestSketch.php
    title: OLED monochrome demo
  - id: canvas
    resource: src/Runners/Sketches/Demos/VL53L1X/CanvasTestSketch.php
    title: Tubes canvas demo
  - id: ux
    resource: src/Runners/Sketches/Demos/VL53L1X/UXCanvasTestSketch.php
    title: UX Scene canvas demo
---

# Role

Optional Workshop sketches that read a VL53L1X via `Waveforms\Distance\Rangefinder::circuit($profile)` and paint live millimetres + a NEAR↔FAR bar scaled from chip `#[MinDistance]`/`#[MaxDistance]` (VL53L1X defaults 40–4000 mm) via `Rangefinder::distanceRange()` → `$rangeMinMm`/`$rangeMaxMm`.

Hard requires stay `gpio-framework` + `waveforms`. Tubes / UX are **suggest** only; demos register only when those packages are present.[^provider]

# Slugs

| Enum case | Slug | Class | Notes |
|-----------|------|-------|-------|
| `OLED` | `vl53l1x-oled-demo` | `OLEDTestSketch` | Requires `MonochromePanel` |
| `CANVAS` | `vl53l1x-canvas-demo` | `CanvasTestSketch` | Rejects mono; baseline when UX absent |
| `UX_ALIAS` | `vl53l1x-ux-canvas-demo` | `UXCanvasTestSketch` | Only when UX installed |

When `scrapyard-io/ux` is installed, provider `SketchRegistry::replace(UXCanvasTestSketch::class)` binds over the **same** `vl53l1x-canvas-demo` slug (tubes↔UX bind-over pattern). Alias keeps an explicit UX entry point.[^enum][^provider]

Namespace: `DeptOfScrapyardRobotics\Sensors\VL53Lxx\Runners\Sketches\Demos\VL53L1X\` (folder `src/Runners/…`).

# Profile resolution (`--profile`)

No CLI args beyond optional `--profile=`:

1. Collect `config/circuits.php` profiles whose `ic === 'vl53l1x'`.
2. **0 profiles** → error and quit.
3. **1 profile** → use it.
4. **>1 profiles** → interactive `choice()`.
5. `--profile=name` overrides; missing / non-`vl53l1x` → error and quit.[^profile]

# Canvas rules

Demos open `tubes.defaults.canvas` the same way as `canvas-window-demo`: **any** `windows.*` or `panels.*` slug (e.g. `metal-canvas`).[^canvas-open]

| Sketch | Gate |
|--------|------|
| OLED | **Require** `MonochromePanel` |
| Canvas / UX canvas | **Reject** `MonochromePanel` only (use OLED slug instead) |

No other canvas-kind gate. HUD: shared tubes `PaintsTubesDistanceHud` (mm + horizontal bar; fill uses chip min/max). UX sketch uses `Assets\RangeHud($minMm, $maxMm)` (`Label` + `ProgressBar`) on a `Scene`.

# Run

```bash
./runner vl53l1x-oled-demo
./runner vl53l1x-canvas-demo
./runner vl53l1x-canvas-demo --profile=vl53l1x
./runner vl53l1x-ux-canvas-demo   # when ux installed
```

# Related

* [Package (0.7)](../orientation/package.md)
* [Circuits integration](circuits.md)
* [VL53Lxx ICs](vl53lxx.md)

[^provider]: Soft sketch registration
[^enum]: Demo slug enum
[^profile]: Profile resolution
[^canvas-open]: Default tubes canvas open (window or panel)
[^oled]: OLED monochrome demo
[^canvas]: Tubes canvas demo
[^ux]: UX Scene canvas demo
