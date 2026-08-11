---
okf_version: "0.2"
---

# dept-of-scrapyard-robotics/vl53lxx Knowledge Bundle

Package knowledge for `dept-of-scrapyard-robotics/vl53lxx` (VL53L0X / VL53L1X ToF rangefinders, v0.7.x).
Read this index first; open only the concepts needed for the task.

**Trust rule:** Prefer `status: stable`. Treat `deprecated` as historical only. New agent-written concepts stay `status: draft` until a human verifies them.
**Placement:** Package-root `.okf/` only — never under `src/`.
**Links:** Concept cross-links use paths relative to each file.
**Scope:** This package’s IC surface and Circuits catalog registration. Registry semantics live in `scrapyard-io/gpio-framework` — do not duplicate that bundle here. Hard deps: gpio-framework + waveforms (not tubes). Tubes/UX are soft suggest for VL53L1X demos only. VL53L5CX is a sibling package (`vl53l5cx`), not folded in here.
**Dist note:** `.okf/` and root `AGENTS.md` are `export-ignore` in `.gitattributes`.

# Orientation

* [Package (0.7)](orientation/package.md) - Composer identity, namespace, Providers SP, dependencies.

# Core

* [VL53Lxx ICs](core/vl53lxx.md) - SensorIC classes, I2C+optional XSHUT Pinout, factories, ranging API.
* [Circuits integration](core/circuits.md) - Catalog slugs `vl53l0x` / `vl53l1x`.
* [VL53L1X demo sketches](core/vl53l1x-demos.md) - Soft tubes/ux runner demos, profile resolution, mono gate only, UX bind-over.

# Traps

* [Fabricate leftovers](traps/fabricate-leftovers.md) - GPI Circuits + waveforms MeasuresDistance / DistanceUnit; not Fabricate Circuits.
* [XSHUT optional](traps/xshut-optional.md) - Optional DigitalIO protocol option; MPSSE shared-context close rules.

# Log

* [Directory update log](log.md)
