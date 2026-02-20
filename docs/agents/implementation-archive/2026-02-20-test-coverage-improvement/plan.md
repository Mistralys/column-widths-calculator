# Plan

## Summary

Improve unit test coverage for `mistralys/column-widths-calculator` based on the findings in the 2026-02-20 unit test audit. The audit identified 9 HIGH-priority, 9 MEDIUM-priority, and 4 LOW-priority missing tests, plus 6 technical debt items. This plan addresses all findings: new test files, refactoring existing tests, fixing two production code issues, and resolving PHPUnit configuration deprecations. The goal is to raise the test count from 8 methods / 28 assertions to comprehensive coverage of all public API methods and critical internal code paths.

## Approach / Architecture

The work is organized into five phases, executed sequentially:

1. **Infrastructure & Technical Debt** — Fix the PHPUnit config, add `declare(strict_types=1)` to `PixelValueTests.php`, enrich `CalculatorTestCase` with shared assertion helpers, and refactor `GetValueTests` to use a data provider.
2. **Production Bug Fixes** — Fix the `OverflowFixer` integer cast bug in float mode, and add a recursion depth guard to `SurplusRemover::remove()`.
3. **HIGH-Priority Tests** — Add 9 new test methods covering `setMaxTotal()`, negative inputs, empty input, float+overflow, float+surplus, float total verification, `SurplusRemover` recursion, exception code assertion, and `getValues()` idempotency.
4. **MEDIUM-Priority Tests** — Add 9 tests covering pixel-value edge cases, `setMinWidth()` boundaries, `getMaxMinWidth()` direct assertions, two-column scenarios, float all-missing, fluent-interface returns, and numeric key preservation.
5. **LOW-Priority Tests** — Add 4 tests for `getDefaultOptions()`, `isIntegerMode()` toggle, many-column stress, and all-equal-value scenario.

New tests will be placed in the most appropriate existing test suite file, or in new suite files where thematically justified. Test methods will follow PHPUnit 13 attribute syntax (no annotations).

## Rationale

- **Infrastructure first:** Shared helpers and data-provider refactoring reduce duplication in all subsequent test writing, making the later phases faster and the tests more readable.
- **Bug fixes before tests:** Fixing `OverflowFixer` and `SurplusRemover` before writing tests for them avoids writing tests that assert incorrect behaviour with `@expectedBug` workarounds.
- **Priority ordering matches audit risk:** HIGH tests target zero-coverage areas and potential production crashes (division-by-zero, infinite recursion). MEDIUM tests cover interaction paths. LOW tests document contracts.
- **Existing file placement when possible:** Tests for `setMaxTotal()` and negative inputs naturally belong in `GetValueTests`; float-related tests in `FloatValueTests`; min-width boundaries in `MinWidthTests`; pixel edge cases in `PixelValueTests`. A new `ConfigurationTests.php` will hold fluent-interface, defaults, and mode-toggle tests. A new `EdgeCaseTests.php` will hold empty-input, idempotency, numeric-key, and stress tests.

## Detailed Steps

### Phase 1 — Infrastructure & Technical Debt

1. **Fix PHPUnit XML configuration.** Remove the four deprecated attributes (`convertErrorsToExceptions`, `convertNoticesToExceptions`, `convertWarningsToExceptions`, `backupStaticAttributes`) from `phpunit.xml`. These were removed in PHPUnit 10 and cause setup warnings.

2. **Add `declare(strict_types=1)` to `PixelValueTests.php`.** Insert `declare(strict_types=1);` as the second line after `<?php`. This is the only project file violating the mandatory strict-types rule.

3. **Add shared assertion helpers to `CalculatorTestCase`.** Add two protected helpers:
   - `assertTotalEquals(int|float $expected, array $values): void` — asserts `array_sum(array_values($values)) === $expected`.
   - `assertCalculation(array $input, array $expectedOutput, ?float $maxTotal = null, ?float $minWidth = null, bool $floatMode = false): void` — creates a `Calculator`, applies optional config, calls `getValues()`, and asserts the output matches expectations. Also asserts the total equals `$maxTotal` (or 100 if null).

4. **Refactor `GetValueTests::test_getValues()` to use a data provider.** Replace the `foreach` loop with a `#[DataProvider('provideGetValuesCases')]` attribute and a static `provideGetValuesCases(): array` method. Each scenario becomes its own labeled test case that runs independently.

### Phase 2 — Production Bug Fixes

5. **Fix `OverflowFixer::fix()` integer cast in float mode.** In `src/Calculator/OverflowFixer.php` line 55, replace `$col->setValue((int)$adjusted);` with a conditional:
   ```php
   if ($this->calculator->isIntegerMode()) {
       $col->setValue((int)$adjusted);
   } else {
       $col->setValue($adjusted);
   }
   ```
   This preserves float precision when float mode is active. The floor() call already provides the necessary rounding in integer mode.

6. **Add recursion depth guard to `SurplusRemover::remove()`.** Add a `private int $depth = 0;` property. At the top of `remove()`, increment `$depth` and check `if ($this->depth > 100) { return; }`. This prevents infinite recursion when all columns are at minimum width and surplus cannot be further absorbed. The safety limit of 100 exceeds any realistic column configuration.

### Phase 3 — HIGH-Priority Tests (H1–H9)

7. **H1 — `setMaxTotal()` basic test.** Add to `GetValueTests`:
   - `test_setMaxTotal()`: Create calculator with values summing below 200, call `setMaxTotal(200)`, verify output sums to 200.
   - `test_setMaxTotal_withOverflowAndMissing()`: Use overflow + missing columns against custom maxTotal; verify output sums to custom total.

8. **H2 — Negative inputs.** Add to `EdgeCaseTests` (new file):
   - `test_negativeInputTreatedAsMissing()`: `Calculator::create(['A' => -10, 'B' => 50, 'C' => 0])` → verify total = 100 and negative-value column gets a positive width.

9. **H3 — Empty input.** Add to `EdgeCaseTests`:
   - `test_emptyInput()`: `Calculator::create([])` → verify the behaviour. If it throws, assert the exception class and code. If it returns `[]`, assert that. (Inspect the actual behaviour first; the audit notes a division-by-zero risk in `getMaxMinWidth()`, but `getValues()` on an empty column set may succeed since `getMaxMinWidth()` is only called from `setMinWidth()`.) Document the discovered behaviour in the test.

10. **H4 — Float mode + overflow.** Add to `FloatValueTests`:
    - `test_floatValues_overflow()`: `['A' => 500, 'B' => 300]` in float mode → verify total = 100 and values are floats (not truncated integers).

11. **H5 — Float mode + surplus removal.** Add to `FloatValueTests`:
    - `test_floatValues_surplus()`: `['A' => 80, 'B' => 20, 'C' => 0]` with `setMinWidth(20)` and `setFloatValues()` → verify total = 100.

12. **H6 — Float mode total verification.** Add total assertion to the existing `test_floatValues()` in `FloatValueTests` and to every new float test:
    - After `$this->assertEquals($expected, $calc->getValues())`, add `$this->assertTotalEquals(100, $calc->getValues())`.

13. **H7 — `SurplusRemover` recursion depth.** Add to `MinWidthTests`:
    - `test_surplusRemover_manyColumnsAtMinWidth()`: Create 10 columns, 9 at value 0, 1 at 100. Set `setMinWidth(9)`. Verify the result terminates and total = 100. (This exercises the recursion guard from step 6.)

14. **H8 — Exception code verification.** Modify `MinWidthTests::test_maxMinWidth()`:
    - Add `$this->expectExceptionCode(Calculator::ERROR_INVALID_MIN_WIDTH);` before the `setMinWidth(35)` call.

15. **H9 — `getValues()` idempotency.** Add to `EdgeCaseTests`:
    - `test_getValuesIdempotency()`: Call `getValues()` twice, assert both returns are identical (`assertSame`).

### Phase 4 — MEDIUM-Priority Tests (M1–M9)

16. **M1 — `getPixelValues()` with missing columns.** Add to `PixelValueTests`:
    - `test_getPixelValues_missingColumns()`: `['A' => 40, 'B' => 0, 'C' => 0]` → `getPixelValues(600)` → verify sum = 600.

17. **M2 — `getPixelValues()` with custom maxTotal.** Add to `PixelValueTests`:
    - `test_getPixelValues_customMaxTotal()`: `setMaxTotal(200)`, supply values in 0–200, `getPixelValues(800)` → verify sum = 800.

18. **M3 — `getPixelValues()` with small targetWidth.** Add to `PixelValueTests`:
    - `test_getPixelValues_smallTargetWidth()`: 5 columns, `getPixelValues(10)` → verify sum = 10 and no negative values.

19. **M4 — `setMinWidth()` boundary values.** Add to `MinWidthTests`:
    - `test_setMinWidth_boundary()`: Test `setMinWidth(0)`, `setMinWidth(1)`, and `setMinWidth(getMaxMinWidth())` — no exception expected. Assert values sum to 100.

20. **M5 — `getMaxMinWidth()` direct assertion.** Add to `MinWidthTests`:
    - `test_getMaxMinWidth()`: Assert return value equals `maxTotal / columnCount` for column counts of 2, 3, and 5.

21. **M6 — Two-column scenarios.** Add to `GetValueTests` (via data provider):
    - Add three new cases to the data provider: two-column normal (exact 100), two-column with one missing, two-column overflow.

22. **M7 — Float mode all columns missing.** Add to `FloatValueTests`:
    - `test_floatValues_allMissing()`: `['A' => 0, 'B' => 0, 'C' => 0]` in float mode → verify total = 100.

23. **M8 — Fluent interface returns.** Add to `ConfigurationTests` (new file):
    - `test_fluentInterface()`: Assert `setFloatValues()`, `setMaxTotal()`, and `setMinWidth()` each return the same `Calculator` instance.

24. **M9 — Numeric key preservation.** Add to `EdgeCaseTests`:
    - `test_numericKeyPreservation()`: `Calculator::create([0 => 50, 1 => 50])` → verify returned keys are `'0'` and `'1'` (string).

### Phase 5 — LOW-Priority Tests (L1–L4)

25. **L1 — `getDefaultOptions()`.** Add to `ConfigurationTests`:
    - `test_getDefaultOptions()`: Assert the returned array matches `['maxTotal' => 100, 'minPerCol' => 1, 'integerValues' => true]`.

26. **L2 — `isIntegerMode()` toggle.** Add to `ConfigurationTests`:
    - `test_isIntegerModeToggle()`: Verify `isIntegerMode()` is `true` by default and `false` after `setFloatValues()`.

27. **L3 — Many columns stress test.** Add to `EdgeCaseTests`:
    - `test_manyColumns()`: 15 columns, mix of zero and non-zero → verify total = 100.

28. **L4 — All-equal non-zero values.** Add to `GetValueTests` (data provider):
    - Add `['A' => 25, 'B' => 25, 'C' => 25, 'D' => 25]` → expected `['A' => 25, 'B' => 25, 'C' => 25, 'D' => 25]`.

### Phase 6 — Post-Implementation

29. **Run `composer dump-autoload`** to regenerate the classmap for any new test files.
30. **Run `vendor/bin/phpunit`** to verify all existing and new tests pass.
31. **Run `vendor/bin/phpstan analyse`** to confirm no static-analysis regressions.
32. **Update project manifest** — update `file-tree.md` to list the two new test suite files (`ConfigurationTests.php`, `EdgeCaseTests.php`).

## Dependencies

- Phase 2 (bug fixes) must complete before Phase 3 tests H4 and H7, which test the fixed behavior.
- Phase 1 step 3 (shared helpers) should complete before Phases 3–5 so tests can use the helpers.
- Phase 1 step 4 (data provider refactor) should complete before steps 21 and 28, which add cases to the provider.

## Required Components

- [phpunit.xml](phpunit.xml) — remove deprecated attributes
- [tests/testsuites/PixelValueTests.php](tests/testsuites/PixelValueTests.php) — add `declare(strict_types=1)`
- [tests/assets/classes/CalculatorTestCase.php](tests/assets/classes/CalculatorTestCase.php) — add shared assertion helpers
- [tests/testsuites/GetValueTests.php](tests/testsuites/GetValueTests.php) — refactor to data provider, add new cases
- [tests/testsuites/FloatValueTests.php](tests/testsuites/FloatValueTests.php) — add 4 new test methods + total assertions
- [tests/testsuites/MinWidthTests.php](tests/testsuites/MinWidthTests.php) — add 3 new test methods + exception code assertion
- [tests/testsuites/PixelValueTests.php](tests/testsuites/PixelValueTests.php) — add 3 new test methods
- `tests/testsuites/ConfigurationTests.php` — **new file** — fluent interface, defaults, mode toggle
- `tests/testsuites/EdgeCaseTests.php` — **new file** — empty input, negative input, idempotency, numeric keys, stress
- [src/Calculator/OverflowFixer.php](src/Calculator/OverflowFixer.php) — fix `(int)` cast in float mode (line 55)
- [src/Calculator/SurplusRemover.php](src/Calculator/SurplusRemover.php) — add recursion depth guard

## Assumptions

- The empty-input case (`Calculator::create([])`) currently does not crash when calling `getValues()` without also calling `setMinWidth()`, since `getMaxMinWidth()` (which divides by column count) is only invoked from `setMinWidth()`. The test in step 9 will verify the actual behavior and document it. If it does crash, a guard clause will be needed in `Calculator::create()` or `getValues()`.
- The `SurplusRemover` recursion guard (depth limit of 100) is a safety net, not a behavior change. In realistic configurations, recursion terminates within a few passes. The limit only prevents stack overflow in degenerate configurations.
- PHPUnit 13 attribute syntax (`#[DataProvider(...)]`, `#[Test]`) is available and preferred over the legacy annotation syntax.

## Constraints

- Every new file must include `declare(strict_types=1)` as the second line.
- All test classes must extend `CalculatorTestCase`.
- All test classes use namespace `Mistralys\WidthsCalculatorUnitTests`.
- `Calculator` must be instantiated via `Calculator::create()`, never directly.
- Classmap autoloading requires `composer dump-autoload` after adding files.
- No Git write commands (add, commit, branch) are part of this plan.

## Out of Scope

- Increasing PHPStan analysis level or fixing PHPStan warnings.
- Adding code-coverage reporting tooling (e.g., Xdebug/PCOV configuration).
- Refactoring internal worker classes beyond the two targeted bug fixes.
- Adding integration or performance benchmarks.
- Modifying the public API surface of `Calculator`.

## Acceptance Criteria

- All 8 existing tests continue to pass (no regressions).
- At least 30 new test methods are added, covering all 22 audit items (H1–H9, M1–M9, L1–L4).
- Every public method of `Calculator` has at least one direct test assertion.
- Every test file includes `declare(strict_types=1)`.
- `phpunit.xml` produces no deprecation warnings.
- The `OverflowFixer` float-mode bug is fixed and verified by test H4.
- The `SurplusRemover` recursion risk is mitigated and verified by test H7.
- `GetValueTests` uses a PHPUnit data provider; each scenario is independently reported.
- `CalculatorTestCase` provides `assertTotalEquals()` and `assertCalculation()` helpers.
- `file-tree.md` lists the two new test files.
- All tests pass when running `vendor/bin/phpunit`.

## Testing Strategy

- **Unit tests only.** All tests create `Calculator` instances via `Calculator::create()`, configure them, and assert output arrays.
- **Data providers** are used wherever multiple input/output scenarios exist for the same behavior (e.g., `getValues()` scenarios, boundary values).
- **Shared helpers** (`assertTotalEquals`, `assertCalculation`) reduce duplication and enforce the "total = maxTotal" invariant consistently.
- **Exception tests** use `expectException()` + `expectExceptionCode()` to verify both type and code.
- **Idempotency tests** use `assertSame()` for strict identity comparison of cached results.
- **Run full suite** after each phase to catch regressions early.

## Risks & Mitigations

| Risk | Mitigation |
|------|------------|
| **Empty-input behavior is undefined** — `Calculator::create([])` may behave unexpectedly in paths beyond `getValues()`. | Step 9 investigates the actual behavior first. If it crashes, a guard clause is added with its own test. The plan accommodates either outcome. |
| **OverflowFixer fix changes existing output** — Float-mode overflow was silently truncating; fixing it changes return values. | The only existing float test (`test_floatValues`) does not trigger overflow, so it is unaffected. The new test H4 verifies the corrected behavior. |
| **SurplusRemover guard masks a deeper logic error** — The depth limit prevents infinite recursion but doesn't fix the root cause. | The depth limit is a proportionate safety net. A deeper redesign of `SurplusRemover` is out of scope for this coverage-improvement effort. The test H7 documents the degenerate case, making it visible for future analysis. |
| **Data provider refactor breaks existing passing test** — Changing the test structure could introduce a false failure. | Run the full suite immediately after the refactor (before adding new tests) to verify parity. |
| **PHPUnit 13 attribute syntax differences** — Attribute names or behaviors may differ slightly from legacy annotations. | Verify attribute imports (`PHPUnit\Framework\Attributes\DataProvider`, `PHPUnit\Framework\Attributes\Test`) are available in the installed PHPUnit 13 version. |
