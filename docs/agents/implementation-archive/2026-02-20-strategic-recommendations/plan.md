# Plan — Strategic Recommendations

## Summary

This plan addresses all nine strategic recommendations surfaced in the synthesis report
`2026-02-20-test-coverage-improvement`. The recommendations span three priority tiers:
two high-priority correctness/robustness issues, three medium-priority quality issues, and
four low-priority cosmetic/convention issues. PHPStan analysis is configured at **level 9**
as specified, and all eight pre-existing level-9 errors will be eliminated. Upon completion
the codebase will be free of known static-analysis errors, contain no latent architectural
risks, and comply fully with the project's coding conventions.

---

## Approach / Architecture

The work is grouped into four logical passes:

1. **Infrastructure** — Fix `phpstan.neon` so that the `composer analyze` script works at
   level 9. This must be the first completed step because subsequent passes rely on running
   PHPStan to verify their own changes.

2. **Correctness** — Resolve the two high-priority items that represent real runtime risk:
   the empty-array `DivisionByZeroError` guard and the `SurplusRemover` instance-state
   refactor.

3. **Quality** — Address the three medium-priority items: clear the eight level-9 PHPStan
   errors and upgrade `assertTotalEquals()` with a `$delta` parameter for consistent
   float-mode assertions.

4. **Cleanup** — Apply the four low-priority cosmetic and convention fixes: the double space
   in `OverflowFixer`, the `<?php`/`declare` header alignment across test files, and the
   misleading idempotency comment in `GetValueTests`.

---

## Rationale

- PHPStan level 9 is the strictest available level and ensures completeness of type coverage.
  Fixing the `.neon` file first means every subsequent diff can be verified immediately.
- The empty-array guard is a user-facing change (converts a cryptic PHP engine error to a
  named domain exception) and belongs in the correctness pass rather than cleanup.
- The `SurplusRemover` refactor is purely internal; no public API changes.
- `assertTotalEquals()` is used in tests only; adding an optional `$delta` parameter is
  backward-compatible.
- Cosmetic items are batched last to keep the higher-priority diffs clean.

---

## Detailed Steps

### Pass 1 — Infrastructure

1. Open `docs/config/phpstan.neon`.
2. Add `level: 9` to the `parameters:` block.
3. Verify `composer analyze` (or `vendor/bin/phpstan analyse --configuration docs/config/phpstan.neon`)
   executes without startup errors and reports the existing eight errors.
4. Update `docs/agents/project-manifest/constraints.md` to document the configured PHPStan
   level (add a row to the dev-tools or static-analysis section).

### Pass 2 — Correctness

#### 2a — Empty-array domain exception guard

5. Add a new error constant to `Calculator`:

   ```php
   public const ERROR_EMPTY_COLUMN_ARRAY = 61502;
   ```

6. In `Calculator::getValues()`, before delegating to `calculate()`, add:

   ```php
   if ($this->operations->countColumns() === 0) {
       throw new \InvalidArgumentException(
           'Cannot calculate widths for an empty column array.',
           self::ERROR_EMPTY_COLUMN_ARRAY
       );
   }
   ```

7. Add a test case to `EdgeCaseTests.php` that asserts:
   - `Calculator::create([])->getValues()` throws `\InvalidArgumentException`
   - The exception code equals `Calculator::ERROR_EMPTY_COLUMN_ARRAY`

8. Remove the existing catch-all note in `constraints.md` ("There is currently no
   user-friendly guard") and replace it with the new contract (named exception, code 61502).

9. Update `api-surface.md`: change the `@throws` annotation on `getValues()` from
   `\DivisionByZeroError` to `\InvalidArgumentException(ERROR_EMPTY_COLUMN_ARRAY)`.

#### 2b — SurplusRemover instance-state refactor

10. In `SurplusRemover.php`:
    - Remove the `private int $depth = 0;` instance property.
    - Keep `remove()` as a public, stateless entry point that calls a new private helper
      `doRemove()`:

      ```php
      public function remove(): void
      {
          $this->doRemove(0);
      }

      private function doRemove(int $depth): void
      {
          if ($depth > 100) {
              return;
          }
          // ... (existing body, replacing recursive $this->remove() calls with
          //      $this->doRemove($depth + 1))
      }
      ```

11. Update `constraints.md` — "Recursion Safety" section: update the description to reflect
    the `doRemove(int $depth)` signature and the statelessness of `remove()`.

12. Update `data-flows.md` — recursion-cap annotation in Flow 2: note the `doRemove`
    entry point.

### Pass 3 — Quality

#### 3a — PHPStan level-9 type annotations

The eight known errors are:

| Location | Error | Fix |
|---|---|---|
| `CalculatorTestCase::assertTotalEquals()` | Untyped `array` param | `@param array<array-key, int\|float> $values` |
| `CalculatorTestCase::assertCalculation()` — `$input` | Untyped `array` param | `@param array<array-key, float>` |
| `CalculatorTestCase::assertCalculation()` — `$expectedOutput` | Untyped `array` param | `@param array<string, int\|float>` |
| `GetValueTests::provideGetValuesCases()` | Untyped return array | `@return array<string, array{array<string,float>, array<string,int>}>` |
| `GetValueTests::test_getValues()` | Untyped parameter | Add `@param` matching provider return shape |
| `Calculator::getMaxTotal()` | `floatval()` called with `mixed` | Narrow with `(float)$this->getOption('maxTotal')` or add `/** @var float */` inline cast |
| `Calculator::getMinWidth()` | `floatval()` called with `mixed` | Same approach as `getMaxTotal()` |
| *(eighth error — confirm exact location by running PHPStan after step 3)* | TBD | TBD |

13. Apply the type-narrowing PHPDoc annotations listed above to `CalculatorTestCase.php`,
    `GetValueTests.php`, and `Calculator.php`.
14. For the two `floatval($this->getOption(...))` call sites, replace with an explicit
    `(float)` cast or introduce a typed intermediate variable, whichever PHPStan accepts
    at level 9.
15. Run PHPStan after each file change. The final run must report **0 errors**.
16. Update `api-surface.md` to reflect any signature refinements on `Calculator` public
    methods (if PHPDoc params are surfaced publicly).

#### 3b — assertTotalEquals() float safety

17. Add an optional `?float $delta` parameter to `assertTotalEquals()` in
    `CalculatorTestCase`:

    ```php
    protected function assertTotalEquals(
        int|float $expected,
        array $values,
        ?float $delta = null
    ): void {
        $actual = array_sum(array_values($values));
        if ($delta !== null) {
            $this->assertEqualsWithDelta($expected, $actual, $delta);
        } else {
            $this->assertSame($expected, $actual);
        }
    }
    ```

18. Replace the `assertEqualsWithDelta()` direct call in `FloatValueTests::test_floatValues_allMissing()`
    with `$this->assertTotalEquals(..., $delta)` to confirm the helper works.
19. Update the `assertTotalEquals()` row in `constraints.md` (Shared Assertion Helpers table)
    to document the new `$delta` parameter.

### Pass 4 — Cleanup

20. **`OverflowFixer.php` double space** — Change `class  OverflowFixer` to `class OverflowFixer`.

21. **`declare` header alignment** — In `EdgeCaseTests.php`, remove the blank line between
    `<?php` and `declare(strict_types=1);` so line 2 carries the `declare` statement,
    matching the project convention and all other test files.

22. **Idempotency comment** — In `GetValueTests.php`, locate the comment that reads
    `"strict identity: same array reference or equal values"` in `test_getValuesIdempotency`
    and replace it with:
    `"strict type+value equality on every element — arrays are value types in PHP"`.

23. **Verify no regressions** — Run the full PHPUnit suite; all 40 tests must pass with 0
    failures and 0 errors.

---

## Dependencies

- `phpstan/phpstan >=2.1.9` — already installed as a dev dependency.
- `phpunit/phpunit >=13.0.5` — already installed as a dev dependency.
- No new packages required.

---

## Required Components

Files that will be modified:

| File | Reason |
|---|---|
| `docs/config/phpstan.neon` | Add `level: 9` directive |
| `src/Calculator.php` | Add `ERROR_EMPTY_COLUMN_ARRAY` constant; add empty-array guard in `getValues()`; fix two `floatval(mixed)` PHPStan errors |
| `src/Calculator/SurplusRemover.php` | Refactor `remove()` / introduce `doRemove(int $depth)` |
| `src/Calculator/OverflowFixer.php` | Fix double-space in class declaration |
| `tests/assets/classes/CalculatorTestCase.php` | Add `$delta` parameter to `assertTotalEquals()`; add PHPDoc type annotations |
| `tests/testsuites/EdgeCaseTests.php` | Add test for `ERROR_EMPTY_COLUMN_ARRAY`; fix blank line after `<?php` |
| `tests/testsuites/FloatValueTests.php` | Update `test_floatValues_allMissing()` to use `assertTotalEquals()` with `$delta` |
| `tests/testsuites/GetValueTests.php` | Add PHPDoc type annotations; fix idempotency comment |
| `docs/agents/project-manifest/api-surface.md` | Update `getValues()` throws annotation; update `assertTotalEquals()` signature |
| `docs/agents/project-manifest/constraints.md` | Update empty-array contract; update Recursion Safety section; add PHPStan level note; update assertion helper table |
| `docs/agents/project-manifest/data-flows.md` | Update recursion annotation in Flow 2 |

No new files are required.

---

## Assumptions

- PHPStan's eighth level-9 error (not fully identified in the synthesis) will be discovered
  by running the analyser after step 3 and resolved within Pass 3 without requiring
  architectural changes.
- No public API signatures change except the addition of `ERROR_EMPTY_COLUMN_ARRAY` and
  the refined `@throws` contract on `getValues()`.
- The `composer analyze` script points to `docs/config/phpstan.neon`; if it does not,
  the script definition in `composer.json` will be updated to pass the correct
  `--configuration` flag.

---

## Constraints

- `declare(strict_types=1)` must remain on line 2 (immediately after `<?php`) in every
  source and test file.
- No internal worker class (`src/Calculator/*.php`) may be exposed through the public API.
- `Calculator::__construct()` remains private; `Calculator::create()` is the only factory.
- `assertTotalEquals()` change must be backward-compatible (new `$delta` parameter is
  optional; all existing callers continue to work without modification).
- The empty-array exception must use a `Calculator` constant error code (consistent with
  `ERROR_INVALID_MIN_WIDTH = 61501`); assign `ERROR_EMPTY_COLUMN_ARRAY = 61502`.

---

## Out of Scope

- Changing the `SurplusRemover` recursion cap from 100 (only the instance-state bug is
  addressed; the cap value itself is not reconsidered).
- Adding new test suites beyond a single test case for the empty-array guard.
- Modifying the `getPixelValues()` pipeline.
- Any change to the `application-utils` dependency version.
- `README.md` changes (no user-facing behaviour changes in this plan).

---

## Acceptance Criteria

- `vendor/bin/phpstan analyse --configuration docs/config/phpstan.neon` reports
  **0 errors at level 9**.
- `vendor/bin/phpunit` reports **40 tests, all passing, 0 failures, 0 errors**.
- `Calculator::create([])->getValues()` throws `\InvalidArgumentException` with code
  `Calculator::ERROR_EMPTY_COLUMN_ARRAY` (61502).
- `SurplusRemover::remove()` contains no instance-state `$depth` property; recursion depth
  is passed as a parameter to `doRemove()`.
- `CalculatorTestCase::assertTotalEquals()` accepts an optional `?float $delta`; existing
  callers compile and pass without modification.
- No double space remains in `class  OverflowFixer`.
- `EdgeCaseTests.php` has `declare(strict_types=1)` on line 2 with no blank line between
  `<?php` and the `declare` statement.
- The idempotency comment in `GetValueTests.php` no longer references "array reference".
- All manifest files listed in "Required Components" are updated to reflect the changes.

---

## Testing Strategy

- The empty-array guard is covered by a new assertion test in `EdgeCaseTests.php`
  (exception type + exception code).
- The `SurplusRemover` refactor is covered by the existing `MinWidthTests` and
  `EdgeCaseTests` suites, which exercise the surplus-removal path; no logic change means
  no new assertions are required beyond confirming all existing tests still pass.
- The `assertTotalEquals()` delta upgrade is exercised by updating
  `test_floatValues_allMissing()` to use the helper.
- PHPStan level 9 at zero errors is itself the verification for the type-annotation pass.
- The full PHPUnit suite (40 tests, 97+ assertions) acts as the regression net for
  all passes.

---

## Risks & Mitigations

| Risk | Mitigation |
|---|---|
| **PHPStan eighth error is in a location requiring a non-trivial fix** | Run PHPStan immediately after step 3 to identify it; if it involves a non-backward-compatible signature change, flag before proceeding |
| **`doRemove()` refactor introduces off-by-one in depth counter** | The counter starts at 0 and the guard triggers at `> 100`; existing `MinWidthTests` suite fully exercises the surplus path and will catch any regression |
| **Optional `$delta` in `assertTotalEquals()` silently masks a genuine precision error** | `$delta` is only passed by callers that consciously opt in; integer-mode callers continue to use strict `assertSame()` |
| **`ERROR_EMPTY_COLUMN_ARRAY = 61502` collides with a future error code** | `constraints.md` will document the assigned code; sequential assignment starting from 61501 is already the established convention |
| **`composer.json` `analyze` script does not reference `phpstan.neon`** | Check `composer.json` scripts block immediately; if absent, add `--configuration docs/config/phpstan.neon` to the script before Pass 3 |
