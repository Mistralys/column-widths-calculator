# Constraints & Conventions

## Language & Typing

- **PHP 8.4+ required.** The `composer.json` enforces `"php": ">=8.4"`.
- **Strict types everywhere.** Every source file opens with `declare(strict_types=1)`. Do not omit this when adding new files.

## Instantiation

- **`Calculator::__construct()` is private.** Always use the factory method `Calculator::create(array $columnValues)`. Attempting to instantiate directly will cause a fatal error.

## Calculation Lifecycle

- **Lazy, single-pass calculation.** `calculate()` is guarded by a `$calculated` boolean flag. It runs exactly once, on the first call to `getValues()`. Subsequent calls return cached column values. Do not add code that modifies `Column` values after `getValues()` has been called.

## Option Defaults

| Option key | Default | Notes |
|---|---|---|
| `maxTotal` | `100` | Target sum for all column widths. |
| `minPerCol` | `1` | Minimum width enforced per column. |
| `integerValues` | `true` | Output integers by default; use `setFloatValues()` to disable. |

## Minimum Width Validation

- `setMinWidth(float $width)` throws `\Exception` with code `Calculator::ERROR_INVALID_MIN_WIDTH` (`61501`) when `$width > getMaxTotal() / columnCount`. This prevents a configuration where the sum of minimum widths would already exceed `maxTotal`.

## Output Modes

- **Integer mode (default):** all values are floored via `intval(floor(...))` before the leftover-filling step. The `LeftoverFiller` then corrects rounding gaps to ensure the total is exact.
- **Float mode:** no rounding is applied. The caller is responsible for any rounding they require.

## Internal vs. Public Classes

- Only `Calculator` is part of the public API. The classes in `src/Calculator/` (`Column`, `Operations`, `MissingFiller`, `LeftoverFiller`, `OverflowFixer`, `SurplusRemover`) are internal workers and **must not** be instantiated or called directly by consumers.

## Autoloading

- Uses **classmap** autoloading, not PSR-4. When adding new source files, run `composer dump-autoload` to regenerate the classmap. File location does not need to follow namespace conventions, but by convention it does.

## Dependency on `application-utils`

- `Calculator` uses `Traits_Optionable` and `Interface_Optionable` from `mistralys/application-utils ^3.0` for its option management. Methods such as `getOption()`, `setOption()`, and `getBoolOption()` come from this trait — do not reimplement option storage independently.

## Namespace

- Root namespace: `Mistralys\WidthsCalculator`
- Internal workers namespace: `Mistralys\WidthsCalculator\Calculator`
- Test classes namespace: `Mistralys\WidthsCalculatorUnitTests`

## Testing

- Tests extend `CalculatorTestCase` (located in `tests/assets/classes/`), which itself extends the PHPUnit base test case.
- Test suites are organised by feature area (`FloatValueTests`, `GetValueTests`, `MinWidthTests`, `PixelValueTests`).
