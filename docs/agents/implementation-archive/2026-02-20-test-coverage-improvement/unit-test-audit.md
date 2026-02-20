# Unit Test Audit: column-widths-calculator

**Audit Date:** 2026-02-20  
**Auditor:** Unit Test Auditor Agent v1.0.0  
**Scope:** Full test suite (`tests/testsuites/`)  
**Framework:** PHPUnit 13.0.5 on PHP 8.5.2  
**Current Status:** 8 tests, 28 assertions — all passing

---

## 1. Executive Summary

**Current State:** The test suite covers the primary happy-path scenarios across the four main feature areas (integer values, float values, minimum width, pixel conversion). However, the suite is thin — only 8 test methods exist for a library with five distinct internal calculation stages and multiple interacting configuration options. Several important configuration combinations and boundary conditions have zero coverage.

**Top Risk:** The `setMaxTotal()` configuration method — which changes the fundamental target sum used by every calculation stage — has **no test coverage at all**. Any regression in custom `maxTotal` handling would go entirely undetected.

---

## 2. Coverage Map (Current State)

| Test File | Tests | What Is Covered | What Is Not |
|---|---|---|---|
| `GetValueTests.php` | 1 method (10 inline cases) | Normal flow, missing flow, overflow flow, single-column, leftover fill | No data provider; failure in one case halts the rest |
| `FloatValueTests.php` | 1 method | Basic float mode with one missing column | No overflow/surplus in float mode; no total verification |
| `MinWidthTests.php` | 5 methods | Min width basic, surplus, alternate surplus, max-min exception | No float+minWidth combo; exception code not verified |
| `PixelValueTests.php` | 1 method | Basic pixel conversion with 3 even columns | No edge cases; missing `declare(strict_types=1)` |

---

## 3. Recommended Tests (Categorized)

### HIGH Priority

| # | Component / Method | Test Description | Reasoning |
|---|---|---|---|
| H1 | `setMaxTotal()` | Create a calculator with `setMaxTotal(200)`, supply values summing below 200, verify output sums to 200. Also test with overflow+missing columns against a custom maxTotal. | **Zero coverage** for a core config option that gates every calculation step. Any regression here silently corrupts all output. |
| H2 | `getValues()` with negative inputs | `Calculator::create(['A' => -10, 'B' => 50, 'C' => 0])` — verify negative-value columns are treated as missing (value ≤ 0 triggers missing flag), and the total still reaches maxTotal. | Negative values set `missing = true` in `Column` but contribute a negative amount to `calcTotal()`. The `MissingFiller` then sees `toDistribute = maxTotal - (negative total)`, which may over-allocate. This is an untested path through `MissingFiller::calcPerColumn()`. |
| H3 | `getValues()` with empty input | `Calculator::create([])` — verify behaviour. Currently, `getMaxMinWidth()` divides by `countColumns()` (0), causing a division-by-zero. If this is expected to throw, test for the exception. If it should return an empty array, the code needs a guard. | **Division-by-zero risk** in production code with no guard and no test. |
| H4 | Float mode + overflow | `Calculator::create(['A' => 500, 'B' => 300])->setFloatValues()->getValues()` — verify that scaling works correctly with floats and total equals exactly `maxTotal`. | The `OverflowFixer` uses `floor()` and casts with `(int)` even in float mode. This may silently truncate precision in float mode — it is a likely bug, and it is completely untested. |
| H5 | Float mode + surplus removal | Supply `['A' => 80, 'B' => 20, 'C' => 0]` in float mode with `setMinWidth(20)`. Verify the surplus is distributed correctly. | The `SurplusRemover::processColumn()` uses `round()` unconditionally. Combined with float mode, it may produce values that don't sum to exactly maxTotal. No test exists for this interaction. |
| H6 | Float mode — total verification | Every float test should assert that the sum of returned values equals `maxTotal`. The one existing float test (`test_floatValues`) does **not** check the total. | The "total = 100" invariant is the library's core guarantee. It is never asserted in float-mode tests. |
| H7 | `SurplusRemover` recursion depth | Create a scenario with many columns at minimum width and one large column, forcing multiple recursive passes of `SurplusRemover::remove()`. Verify the result is stable and terminates. | `SurplusRemover::remove()` calls itself recursively with no depth limit. If the surplus cannot be absorbed (all columns at min width), no termination condition prevents unbounded recursion. |
| H8 | `setMinWidth()` — exception code | In `test_maxMinWidth`, add `$this->expectExceptionCode(Calculator::ERROR_INVALID_MIN_WIDTH)` to verify the correct error code is thrown, not just any `Exception`. | The exception code (61501) is part of the public API surface. The test only checks for `Exception::class`, not the code. A refactor could accidentally change the code without detection. |
| H9 | `getValues()` idempotency | Call `getValues()` twice on the same instance and verify both calls return identical results. | Ensures the lazy-calculation guard (`$calculated` flag) works correctly and cached results are returned. Currently untested. |

### MEDIUM Priority

| # | Component / Method | Test Description | Reasoning |
|---|---|---|---|
| M1 | `getPixelValues()` with missing columns | `Calculator::create(['A' => 40, 'B' => 0, 'C' => 0])->getPixelValues(600)` — verify pixel values sum to 600 and missing columns get proportional pixel widths. | `getPixelValues()` delegates to a second `Calculator` instance. The interaction with missing-column handling through two calculation passes is untested. |
| M2 | `getPixelValues()` with custom `maxTotal` | Set `maxTotal(200)`, supply values in 0–200 range, then call `getPixelValues(800)`. Verify pixels sum to 800. | Tests the interaction between a non-default maxTotal and the pixel conversion pipeline. |
| M3 | `getPixelValues()` with small `targetWidth` | `getPixelValues(10)` on 5+ columns — verify pixel values sum to 10 and no value is negative. | Stress-tests the pixel pipeline when target width is too small to distribute evenly, especially with integer rounding. |
| M4 | `setMinWidth()` boundary values | Test `setMinWidth(0)`, `setMinWidth(1)`, and `setMinWidth(getMaxMinWidth())` — verify no exception is thrown on the exact boundary. | Boundary-value analysis: `setMinWidth` only throws when `$width > getMaxMinWidth()`. The exact boundary (equal) should be accepted. |
| M5 | `getMaxMinWidth()` return value | Directly assert `getMaxMinWidth()` returns `maxTotal / columnCount` for various column counts. | The method is only indirectly tested through the exception test. A direct assertion provides stronger regression detection. |
| M6 | Two-column scenarios | Test all flows (normal, missing, overflow) with exactly 2 columns. | Proportional distribution changes with small column counts. The `LeftoverFiller` iterates backwards and may disproportionately adjust the last column. Currently, most tests use 3+ columns. |
| M7 | Float mode — all columns missing | `Calculator::create(['A' => 0, 'B' => 0, 'C' => 0])->setFloatValues()->getValues()` | No float-mode test covers the all-zero input path. Distribution of `maxTotal / count` in float precision may produce rounding artifacts. |
| M8 | `setFloatValues()` return type | Verify `setFloatValues()` returns the `Calculator` instance (fluent interface). Same for `setMaxTotal()` and `setMinWidth()`. | Fluent chaining is a public API contract. If a refactor forgets a `return $this`, consumer code breaks. No assertion exists. |
| M9 | Column name preservation with numeric keys | `Calculator::create([0 => 50, 1 => 50])` or `Calculator::create(['1' => 50, '2' => 50])` — verify returned keys match input keys. | PHP array key coercion may silently convert string-numeric keys to integers. The constructor casts with `(string)$name`, but the behavior should be explicitly tested. |

### LOW Priority

| # | Component / Method | Test Description | Reasoning |
|---|---|---|---|
| L1 | `getDefaultOptions()` | Assert the returned array matches `['maxTotal' => 100, 'minPerCol' => 1, 'integerValues' => true]`. | Documents the contract in a test. Low risk since defaults rarely change, but protects against accidental edits. |
| L2 | `isIntegerMode()` toggle | Verify `isIntegerMode()` returns `true` by default and `false` after `setFloatValues()`. | Simple getter coverage. |
| L3 | Many columns (10+) | Test with 10–20 columns, mix of zero and non-zero values. Verify total = maxTotal. | Stress test for proportional algorithms under higher column counts. |
| L4 | All-equal non-zero values | `['A' => 25, 'B' => 25, 'C' => 25, 'D' => 25]` — verify no leftover adjustment needed. | Validates the exact-match fast path where no correction steps fire. |

---

## 4. Technical Debt Observations

### 4.1 Missing `declare(strict_types=1)` in PixelValueTests.php

[PixelValueTests.php](tests/testsuites/PixelValueTests.php) is missing the mandatory `declare(strict_types=1)` declaration.  
Per the project constraints, **every** source file must include this. This is the only file in the project that violates this rule.

### 4.2 Deprecated PHPUnit XML Configuration

[phpunit.xml](phpunit.xml) uses attributes removed in PHPUnit 10+:
- `convertErrorsToExceptions`
- `convertNoticesToExceptions`
- `convertWarningsToExceptions`
- `backupStaticAttributes`

PHPUnit emits a configuration deprecation warning. Run `vendor/bin/phpunit --migrate-configuration` to fix.

### 4.3 Data-Driven Test Without Data Provider

[GetValueTests.php](tests/testsuites/GetValueTests.php) packs 10 scenarios into a single `test_getValues()` method using a `foreach` loop. If any case fails, subsequent cases are skipped. Using PHPUnit's `#[DataProvider]` attribute would give each case its own test identity in reporting and allow all cases to run independently.

### 4.4 Empty Base Test Case

[CalculatorTestCase.php](tests/assets/classes/CalculatorTestCase.php) contains no shared helpers. The recurring pattern of "create calculator → get values → assert total → assert values" appears across all tests and could be extracted into assertion helpers:
- `assertTotalEquals(int|float $expected, array $values)` — verify sum of returned values
- `assertCalculation(array $input, array $expectedOutput, ?float $maxTotal = null)` — full pipeline assertion

### 4.5 OverflowFixer Uses Integer Cast in All Modes

In [OverflowFixer.php](src/Calculator/OverflowFixer.php#L55), the adjusted value is cast with `(int)`:
```php
$col->setValue((int)$adjusted);
```
This truncation occurs regardless of whether `isIntegerMode()` is true. In float mode, this silently loses decimal precision. This is likely a **bug** rather than intent — the float-mode code path should preserve fractional values through the overflow step. A test for float-mode + overflow (H4) would detect this.

### 4.6 Potential Infinite Recursion in SurplusRemover

`SurplusRemover::remove()` ([SurplusRemover.php](src/Calculator/SurplusRemover.php#L64)) calls itself recursively when `$this->leftover > 0`. If **every** column is already at `minWidth` and surplus remains, `processColumn()` returns `true` without reducing any value, `leftover` never reaches 0, and recursion never terminates. While current configurations may not trigger this, there is no safety depth limit. A test (H7) would expose the issue and prompt a guard clause.

---

## 5. Risk Heat Map

```
                           LOW coverage ◄──────────────── HIGH coverage
                          ┌──────────────────────────────────┐
  HIGH complexity/risk    │  setMaxTotal()         ■         │
                          │  Float+Overflow        ■         │
                          │  SurplusRemover recur. ■         │
                          │  Empty input           ■         │
                          │  Negative inputs       ■         │
                          │                                  │
  MEDIUM complexity/risk  │  PixelValues edge   ■            │
                          │  Float+Surplus      ■            │
                          │  MinWidth boundary  ■            │
                          │  2-column scenarios ■            │
                          │                                  │
  LOW complexity/risk     │  Defaults              ■         │
                          │  isIntegerMode()       ■         │
                          │  Equal-value columns        ■    │
                          │  Normal flow (≤100)            ■ │
                          │  Basic missing fill            ■ │
                          └──────────────────────────────────┘
```

---

## 6. Summary Metrics

| Metric | Value |
|---|---|
| Total test methods | 8 |
| Total assertions | 28 |
| Source methods (public) | 14 |
| Source methods with direct coverage | 5 (`create`, `getValues`, `setFloatValues`, `setMinWidth`, `getPixelValues`) |
| Source methods with **zero** coverage | 4 (`setMaxTotal`, `getMaxTotal`, `getMaxMinWidth`, `isIntegerMode`) |
| Configuration combinations tested | 3 of ~12 meaningful combos |
| Recommended HIGH-priority tests | 9 |
| Recommended MEDIUM-priority tests | 9 |
| Recommended LOW-priority tests | 4 |
| Likely bugs found | 1 (OverflowFixer integer cast in float mode) |
| Potential stability risks | 1 (SurplusRemover unbounded recursion) |

---

AGENT: Unit Test Auditor  
STATUS: AUDIT_COMPLETE
