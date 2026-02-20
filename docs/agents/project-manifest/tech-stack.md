# Tech Stack & Patterns

## Runtime & Language

| Item | Value |
|---|---|
| Language | PHP |
| Minimum Version | 8.4 |
| Type Strictness | `declare(strict_types=1)` in every source file |

## Package Manager

- **Composer** — dependency management and classmap autoloading.

## Autoloading Strategy

- **Classmap** (not PSR-4). Composer maps all classes in `src/` and `tests/assets/classes/` by scanning filenames.

## Production Dependencies

| Package | Version Constraint | Purpose |
|---|---|---|
| `mistralys/application-utils` | `^3.0` | Provides `Interface_Optionable` and `Traits_Optionable` for configuration option management on `Calculator`. |

## Development Dependencies

| Package | Version Constraint | Purpose |
|---|---|---|
| `phpunit/phpunit` | `>=13.0.5` | Unit testing framework. |
| `phpstan/phpstan` | `>=2.1.9` | Static analysis. |

## Architectural Patterns

- **Single public entry point:** `Calculator` is the only class consumers interact with directly. All worker classes in `Calculator/` are internal implementation details.
- **Facade + Strategy workers:** `Calculator` delegates each transformation step to a dedicated single-responsibility worker class (`MissingFiller`, `OverflowFixer`, `SurplusRemover`, `LeftoverFiller`).
- **Shared utility object:** `Operations` provides read-only calculation helpers shared across all worker classes, avoiding duplication.
- **Lazy evaluation:** Column widths are only calculated on the first call to `getValues()` or `getPixelValues()`.
- **Options pattern:** `Calculator` uses `Traits_Optionable` (from `application-utils`) to manage configuration options such as `maxTotal`, `minPerCol`, and `integerValues`.
- **Factory method:** Constructor is private; instances are created via `Calculator::create()`.
