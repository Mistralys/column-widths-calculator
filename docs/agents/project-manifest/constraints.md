# Constraints & Conventions

## Language & Typing

- **PHP 8.4+ required.** The `composer.json` enforces `"php": ">=8.4"`.
- **Strict types everywhere.** Every source file opens with `declare(strict_types=1)`. Do not omit this when adding new files.

## Instantiation

- **`Calculator::__construct()` is private.** Always use the factory method `Calculator::create(array $columnValues)`. Attempting to instantiate directly will cause a fatal error.

## Calculation Lifecycle

- **Lazy, single-pass calculation.** `calculate()` is guarded by a `$calculated` boolean flag. It runs exactly once, on the first call to `getValues()`. Subsequent calls return cached column values (idempotent). Do not add code that modifies `Column` values after `getValues()` has been called.

## Input Edge Cases

- **Negative values treated as missing.** Any column whose initial value is `<= 0` (including negative numbers) is flagged as *missing* by `Column::isMissing()` and receives a filled width during the `MissingFiller` pass. Callers should not rely on negative values being preserved.
- **Empty column array throws `\InvalidArgumentException`.** `Calculator::create([])` succeeds, but calling `getValues()` on an empty-column calculator throws `\InvalidArgumentException` with code `Calculator::ERROR_EMPTY_COLUMN_ARRAY` (61502). The guard is applied at the top of `getValues()` before the calculation pipeline runs.

## Option Defaults

| Option key | Default | Notes |
|---|---|---|
| `maxTotal` | `100` | Target sum for all column widths. |
| `minPerCol` | `1` | Minimum width enforced per column. |
| `integerValues` | `true` | Output integers by default; use `setFloatValues()` to disable. |

## Minimum Width Validation

- `setMinWidth(float $width)` throws `\InvalidArgumentException` with code `Calculator::ERROR_EMPTY_COLUMN_ARRAY` (`61502`) when called on a calculator with an empty column array (i.e., created with `Calculator::create([])`). This guard fires before the width comparison and prevents a `DivisionByZeroError` in `getMaxMinWidth()`.
- `setMinWidth(float $width)` throws `\InvalidArgumentException` with code `Calculator::ERROR_INVALID_MIN_WIDTH` (`61501`) when `$width > getMaxTotal() / columnCount`. This prevents a configuration where the sum of minimum widths would already exceed `maxTotal`.
- `getMaxMinWidth()` returns `0.0` when the column array is empty (no division is attempted).

## Output Modes

- **Integer mode (default):** all values are floored via `intval(floor(...))` before the leftover-filling step. The `LeftoverFiller` then corrects rounding gaps to ensure the total is exact.
- **Float mode:** no rounding is applied. The caller is responsible for any rounding they require. When `OverflowFixer` runs in float mode, adjusted values are assigned as floats (the `floor()` call is still applied, so overflow redistribution may yield whole-number floats such as `50.0` rather than fractional values like `50.5`).

## Recursion Safety

- `SurplusRemover::remove()` is a stateless entry point that delegates immediately to the private `doRemove(int $depth)` helper, calling it with `$this->doRemove(0)`. All surplus-removal logic and recursion live in `doRemove()`. The depth guard fires at `$depth > 100`, returning immediately to cap recursion at **100 iterations** and prevent a stack overflow in degenerate configurations where all columns are at `minWidth` and cannot absorb surplus. In normal operation, convergence happens in 1–3 passes. There is no `$depth` instance property — the counter is passed as a method argument, making each `remove()` call fully stateless.

## Internal vs. Public Classes

- Only `Calculator` is part of the public API. The classes in `src/Calculator/` (`Column`, `Operations`, `MissingFiller`, `LeftoverFiller`, `OverflowFixer`, `SurplusRemover`) are internal workers and **must not** be instantiated or called directly by consumers.

## Autoloading

- Uses **classmap** autoloading, not PSR-4. When adding new source files, run `composer dump-autoload` to regenerate the classmap. File location does not need to follow namespace conventions, but by convention it does.

## Dependency on `application-utils`

- `Calculator` uses `Traits_Optionable` and `Interface_Optionable` from `mistralys/application-utils ^3.0` for its option management. Methods such as `getOption()`, `setOption()`, and `getBoolOption()` come from this trait — do not reimplement option storage independently.
- **`getOption()` returns `mixed`.** When reading a numeric option and performing arithmetic or returning a typed `float`, you must narrow the type explicitly using an `is_numeric()` guard before casting. A bare `(float)` cast on a `mixed` value is rejected by PHPStan at level 9. Use the pattern below:
  ```php
  $raw = $this->getOption('myNumericOption');
  return is_numeric($raw) ? (float)$raw : 0.0;
  ```
  `getBoolOption()` is already typed and does not require this guard.

## Namespace

- Root namespace: `Mistralys\WidthsCalculator`
- Internal workers namespace: `Mistralys\WidthsCalculator\Calculator`
- Test classes namespace: `Mistralys\WidthsCalculatorUnitTests`

## Code Style

- **Tool:** `friendsofphp/php-cs-fixer` (`^3.94`)
- **Configuration file:** `.php-cs-fixer.php` (tracked in version control; do **not** add to `.gitignore`).
- **Base rule set:** `@PSR12` with the following overrides:
  - `braces_position` → functions and classes use Allman-style opening brace (`next_line_unless_newline_at_signature_end`); control structures use same-line (`same_line`).
  - `no_spaces_after_function_name` → enabled.
- **Apply fixes:** `composer cs-fix`
- **Dry-run check:** `composer cs-check` (exits with code 0 when no changes are needed)
- The dry-run must report **0 changes** on any committed code. Run `composer cs-check` before marking a WP complete.

## Static Analysis

- **PHPStan is configured at level 9** via `docs/config/phpstan.neon`. Run the analyser with `composer analyze` (which passes `--configuration docs/config/phpstan.neon`) or directly with `vendor/bin/phpstan analyse --configuration docs/config/phpstan.neon`.
- The codebase currently reports **0 errors at level 9**. All type-annotation and narrowing issues have been resolved. New contributions must not introduce regressions — verify with `composer analyze` before committing.

## Testing

- Tests extend `CalculatorTestCase` (located in `tests/assets/classes/`), which itself extends the PHPUnit base test case.
- Test suites are organised by feature area (`ConfigurationTests`, `EdgeCaseTests`, `FloatValueTests`, `GetValueTests`, `MinWidthTests`, `PixelValueTests`).

### Shared Assertion Helpers (CalculatorTestCase)

Two protected helpers are available to all test cases:

| Method | Signature | Purpose |
|---|---|---|
| `assertTotalEquals()` | `assertTotalEquals(int\|float $expected, array<array-key, int\|float> $values, ?float $delta = null): void` | Asserts that `array_sum($values) === $expected`. When `$delta` is `null`, uses `assertSame` (strict type check; `100` and `100.0` are distinct). When `$delta` is non-null, uses `assertEqualsWithDelta($expected, $actual, $delta)` for floating-point tolerance comparisons. |
| `assertCalculation()` | `assertCalculation(array $input, array $expectedOutput, int\|float\|null $maxTotal = null, int\|float\|null $minWidth = null, bool $floatMode = false): void` | Creates a `Calculator` from `$input`, applies any optional configuration, calls `getValues()`, and asserts both the output map and the total sum. |

> **Note:** When passing a `$minWidth` argument to `assertCalculation()`, ensure the value satisfies `$minWidth <= getMaxMinWidth()`. The helper does not pre-validate this; an invalid value will surface as a `Calculator::ERROR_INVALID_MIN_WIDTH` (61501) exception from inside the helper.

### Data Provider Convention

Multi-scenario tests use the PHPUnit 13 `#[DataProvider('methodName')]` attribute (not the deprecated `@dataProvider` annotation). Provider methods must be `public static`, return a keyed array of scenario arrays, and use **descriptive string keys** so each case appears with a human-readable label in the PHPUnit output.
