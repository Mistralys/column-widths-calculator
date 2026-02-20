# Synthesis Report — Test Coverage Improvement
**Plan:** `2026-02-20-test-coverage-improvement`
**Date:** 2026-02-20
**Status:** COMPLETE — All 6 work packages delivered

---

## Executive Summary

This project cycle transformed a minimal 8-test suite into a **40-test, 97-assertion harness** covering all public API paths, edge cases, float-mode behaviour, pixel-value calculations, configuration, and stress scenarios. Two **production bugs were discovered and fixed** during implementation: a silent float-precision bug in `OverflowFixer` and a latent infinite-recursion risk in `SurplusRemover`. A pre-existing **invalid JSON** defect in `composer.json` that was silently blocking all Composer operations was also repaired. All acceptance criteria across all six work packages were fully met.

---

## Metrics

| Metric | Before | After |
|---|---|---|
| Total tests | 8 | **40** |
| Total assertions | ~10 | **97** |
| Failed tests | 0 | **0** |
| PHPStan errors (level 5) | n/a | **0** |
| PHPStan errors (level 9, pre-existing) | 8 | **8** (unchanged) |
| Security issues | 0 | **0** |
| Production bugs fixed | — | **3** |
| New test files | 0 | **2** |

### Test Counts by Work Package

| WP | Delivered | Cumulative |
|---|---|---|
| WP-001 | 9 new cases (GetValueTests data-provider refactor) | 17 |
| WP-002 | 0 new tests (production fixes only) | 17 |
| WP-003 | 8 new tests | 25 |
| WP-004 | 11 new tests | 36 |
| WP-005 | 4 new tests | 40 |
| WP-006 | Integration / validation (0 new) | **40** |

---

## Deliverables

### New Test Files
| File | Tests | Coverage Area |
|---|---|---|
| `tests/testsuites/EdgeCaseTests.php` | 5 | Negative inputs, empty array, idempotency, numeric key preservation, 15-column stress |
| `tests/testsuites/ConfigurationTests.php` | 3 | Fluent interface chaining, `getDefaultOptions()`, `isIntegerMode()` toggle |

### Existing Test Files Expanded
| File | Tests Added |
|---|---|
| `tests/testsuites/GetValueTests.php` | 3 two-column data-provider cases; `test_setMaxTotal()`; `test_setMaxTotal_withOverflowAndMissing()`; `all-equal-four-cols` case |
| `tests/testsuites/FloatValueTests.php` | `test_floatValues_overflow()`, `test_floatValues_surplus()`, `test_floatValues_allMissing()`; total assertion added to `test_floatValues()` |
| `tests/testsuites/MinWidthTests.php` | `test_setMinWidth_boundary()`, `test_getMaxMinWidth()`, `test_surplusRemover_manyColumnsAtMinWidth()`; `expectExceptionCode` added to `test_maxMinWidth()` |
| `tests/testsuites/PixelValueTests.php` | `test_getPixelValues_missingColumns()`, `test_getPixelValues_customMaxTotal()`, `test_getPixelValues_smallTargetWidth()` |

### Test Infrastructure Improvements
- **`phpunit.xml`** — Removed 4 deprecated PHPUnit attributes; zero config deprecation warnings.
- **`tests/assets/classes/CalculatorTestCase.php`** — Added `assertTotalEquals()` and `assertCalculation()` protected helpers; eliminated duplicated assertion boilerplate across all suites.
- **`tests/testsuites/GetValueTests.php`** — Migrated `test_getValues()` from loop to 10-case `#[DataProvider]`.

### Production Bug Fixes (Source Code)
| File | Bug | Fix |
|---|---|---|
| `src/Calculator/OverflowFixer.php` | Unconditional `(int)$adjusted` cast discarded float precision | Replaced with `isIntegerMode()` conditional branch |
| `src/Calculator/SurplusRemover.php` | No recursion depth guard — infinite loop in degenerate configs | Added `private int $depth = 0` + `> 100` guard in `remove()` |
| `composer.json` | Trailing comma in `scripts` block → invalid JSON blocking all Composer ops | Removed trailing comma |

### Documentation Updates
- `docs/agents/project-manifest/constraints.md` — Testing helpers table, data-provider convention, input edge cases, recursion safety, and output mode precision rules.
- `docs/agents/project-manifest/api-surface.md` — `getValues()` idempotency, `DivisionByZeroError` contract, `Calculator::create()` parameter widened to `array<array-key, float>`.
- `docs/agents/project-manifest/data-flows.md` — Recursion cap annotation in Flow 2; integer/float branching in Flow 3.
- `docs/agents/project-manifest/file-tree.md` — `EdgeCaseTests.php`, `ConfigurationTests.php` added with accurate annotations.
- `README.md` — Edge cases section, fluent interface section, `isIntegerMode()` documentation added.
- `changelog.md` — v2.1.0 entry for both production bug fixes.

---

## Incidents

| Priority | Summary | WP | Resolved |
|---|---|---|---|
| HIGH | `composer.json` had a trailing comma making it invalid JSON, silently blocking all `composer dump-autoload` operations | WP-003 | ✅ Yes |

---

## Strategic Recommendations (Gold Nuggets)

These are cross-cutting findings surfaced by Reviewer and QA agents across multiple WPs.

### 🔴 High Priority

1. **`SurplusRemover.$depth` is instance state (WP-002, WP-002 review)**
   The recursion depth counter accumulates across calls on the same instance. Under the current single-pass/cached contract this is safe, but any multi-pass architecture change would silently exhaust the 100-call budget on a second invocation. Recommended fix: refactor into a private `doRemove(int $depth = 0): void` method, keeping `remove()` as a stateless entry point.

2. **Empty column array throws raw `DivisionByZeroError` (WP-003, WP-006)**
   `Calculator::create([])->getValues()` crashes with a PHP engine error rather than a domain exception. A guard in `Calculator::getValues()` or `Operations::countColumns()` should throw an `InvalidArgumentException` (or a named `Calculator` exception with a constant error code, consistent with `ERROR_INVALID_MIN_WIDTH`) before any division occurs.

### 🟡 Medium Priority

3. **`docs/config/phpstan.neon` missing `level:` directive (WP-004, WP-005, WP-006)**
   Running `vendor/bin/phpstan analyse` without `--level N` fails entirely. The composer `analyze` script is therefore non-functional as-is. Fix: add `level: 5` (or the team's target level) to the `parameters:` block in `phpstan.neon`.

4. **`assertTotalEquals()` uses `assertSame()` — latent float footgun (WP-004, WP-004 review)**
   Strict type equality breaks on sub-epsilon float imprecision (e.g., `99.99999999989998`). WP-004 worked around this in `test_floatValues_allMissing()` by using `assertEqualsWithDelta()` directly. The helper should be upgraded with an optional `$delta` parameter or a `assertTotalEqualsFloat()` sibling to make all float-mode tests use the helper consistently.

5. **PHPStan level-9 untyped array debt (WP-003, WP-004)**
   Eight pre-existing level-9 errors remain: untyped `array` params in `CalculatorTestCase::assertTotalEquals()`, `assertCalculation()`, `GetValueTests::provideGetValuesCases()`, and `test_getValues()`; plus `floatval()` called with `mixed` in `Calculator.php` (lines 96, 161). All resolvable with `@param array<string, int|float>` PHPDoc annotations and a type-narrowing guard at the two `floatval()` call sites.

### 🟢 Low Priority

6. **`OverflowFixer.php` double-space in class declaration (WP-002)**
   `class  OverflowFixer` — cosmetic, pre-existing. Correct opportunistically.

7. **`Calculator::create()` PHPDoc narrowness (resolved in WP-004)**
   Updated from `array<string,float>` to `array<array-key, float>` to reflect that integer-keyed arrays are accepted at runtime. ✅ Done.

8. **Test file `<?php` / `declare` blank-line inconsistency (WP-005, WP-006)**
   `EdgeCaseTests.php` has a blank line between `<?php` and `declare(strict_types=1)`, violating the AGENTS.md constraint that mandates `declare` on line 2. `ConfigurationTests.php` is correct. A single-pass cleanup should align all test file headers.

9. **`GetValueTests.php` data-provider case key commentary in `test_getValuesIdempotency` (WP-003, WP-006)**
   Inline comment reads "strict identity: same array reference or equal values" — PHP arrays are value types; `assertSame()` performs deep value equality, not reference identity. Update comment to: "strict type+value equality on every element — arrays are value types in PHP".

---

## Next Steps for Planning

In priority order:

1. **Add input guard for empty array** — prevent `DivisionByZeroError`; throw a named domain exception with a constant error code.
2. **Add `level: 5` to `docs/config/phpstan.neon`** — make the `analyze` Composer script functional.
3. **Refactor `SurplusRemover.remove()` to `doRemove(int $depth=0)`** — eliminate instance-state coupling before any future multi-pass architecture.
4. **Upgrade `assertTotalEquals()` in `CalculatorTestCase`** — add `$delta` parameter or `assertTotalEqualsFloat()` sibling for consistent float-mode assertions.
5. **Clear PHPStan level-9 debt** — 8 pre-existing errors; estimated ~30 min of annotation work.
6. **Cosmetic/convention cleanup pass** — fix `class  OverflowFixer` double space, align all test file `declare` headers, update idempotency comment.

---

*Report generated by Synthesis Agent — 2026-02-20*
