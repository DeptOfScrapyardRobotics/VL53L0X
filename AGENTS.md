# Agent guidelines — dept-of-scrapyard-robotics/vl53lxx

## Knowledge Bundle (OKF)

This package ships an Open Knowledge Format bundle at [`.okf/`](.okf/) (excluded from Composer dist via `.gitattributes` `export-ignore`).

Before changing this package or advising on VL53L0X / VL53L1X architecture:

1. Read [`.okf/index.md`](.okf/index.md) first (progressive disclosure).
2. Open only the linked concepts needed for the task.
3. Prefer `status: stable` concepts; treat `deprecated` as historical only. New/changed concepts stay `status: draft` until a human verifies them.
4. When you learn something durable about **this package**, update the affected `.okf` concept(s) and append `.okf/log.md`.
5. Keep the `.okf` bundle at the **package root** only — do not nest extra `.okf` folders under `src/`.
6. Circuits registry semantics belong in `scrapyard-io/gpio-framework`’s `.okf`. VL53L5CX knowledge belongs in `vl53l5cx`, not here.

## Package rules (quick) — 0.7.x

- Composer: `dept-of-scrapyard-robotics/vl53lxx` **0.7.0**. Namespace `DeptOfScrapyardRobotics\Sensors\VL53Lxx\`.
- Provider: `Providers\VL53LxxServiceProvider`. Catalog slugs `vl53l0x` / `vl53l1x`.
- Requires `scrapyard-io/gpio-framework ^0.7` + `scrapyard-io/waveforms ^0.7` — **no hard tubes/ux**.
- Soft demos (suggest tubes/ux): `vl53l1x-oled-demo`, `vl53l1x-canvas-demo` (+ UX bind-over / `vl53l1x-ux-canvas-demo`). Profile: 0 quit / 1 auto / >1 prompt / `--profile=` override. Canvas = `tubes.defaults.canvas` (window or panel). OLED requires `MonochromePanel`; canvas demos reject mono only. See `.okf/core/vl53l1x-demos.md`.
- ICs extend `GeneralPurposeIO\Circuits\Types\SensorIC`, implement `BootSequence` + `Waveforms\Distance\MeasuresDistance`; factories `i2c(…)` / `fromI2CBus(…)`.
- Attributes: `#[IntegratedCircuit('I2C', ['I2C', 'DigitalIO'])]` + index-aligned `#[Pinout]` (I2C alone or I2C+DigitalIO with `xshut`).
- Boot uses `BootScaffolding`; wire path is `VL53LxxCarrierTransport` over `I2CSlave`.
- Units: `Waveforms\Contracts\Sensors\Enums\DistanceUnit` — no package-local DistanceUnit.
- XSHUT is optional but recommended; `close()` closes transport only (never MPSSE-close via the pin).
- Never import `Fabricate\Contracts\Circuits\*` or `Fabricate\Circuits\*`.
