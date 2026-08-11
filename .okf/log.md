# Directory Update Log

## 2026-08-11

* **Update (draft)**: Demo bar scale syncs from VL53L1X `#[MinDistance]`/`#[MaxDistance]` (40–4000 mm) via `Rangefinder::distanceRange()` into `$rangeMinMm`/`$rangeMaxMm` (tubes HUD + UX `RangeHud`).
* **Correction (draft)**: Removed invented panels-only gate. `OpensDefaultTubesCanvas` opens `tubes.defaults.canvas` as window **or** panel (same as `canvas-window-demo`). Only Angel-locked gate remains: OLED requires `MonochromePanel`; canvas/UX reject mono.
* **Update (draft)**: VL53L1X soft demo sketches under `src/Runners/Sketches/Demos/VL53L1X/` — shared profile resolution (0/1/>1 + `--profile`), OLED requires MonochromePanel, canvas/UX reject mono, UX `replace` bind-over of `vl53l1x-canvas-demo`. New concept [VL53L1X demo sketches](core/vl53l1x-demos.md); package orientation + AGENTS/README amended. Suggest `tubes` + `ux`.
* **Update (draft)**: Both VL53L0X and VL53L1X implement `Waveforms\Distance\MeasuresDistance`; units are `Waveforms\Contracts\Sensors\Enums\DistanceUnit` (package-local DistanceUnit removed). Composer requires `scrapyard-io/waveforms ^0.7`. Amended traps / AGENTS / README.
* **Creation**: Initial `.okf` for `dept-of-scrapyard-robotics/vl53lxx` 0.7 — package orientation, IC surface (VL53L0X/L1X SensorIC, I2C+optional XSHUT factories, ranging), Circuits catalog `vl53l0x`/`vl53l1x`, Fabricate leftovers + XSHUT-optional traps, lean `AGENTS.md`. Corrective backfill after 0.7 promotion shipped without the knowledge bundle (`.gitattributes` already `export-ignore`d `.okf` / `AGENTS.md`).
