# Synthesis Report — Strategic Recommendations

**Plan:** `2026-02-20-strategic-recommendations`
**Date:** 2026-02-20
**Status:** COMPLETE — all 6 work packages delivered

---

## Executive Summary

This session addressed all nine strategic recommendations surfaced by the prior
`2026-02-20-test-coverage-improvement` synthesis. Six work packages were executed across
four logical passes — infrastructure, correctness, quality, and cleanup — by the Developer,
QA, Reviewer, and Documentation agents.

The codebase exits the session in a substantially stronger state:

- **PHPStan level 9** is now fully configured and reports **0 errors** (down from 8 at the start).
- **A latent runtime crash** (`DivisionByZeroError` on an empty column array) has been
  converted to a proper named domain exception (`Calculator::ERROR_EMPTY_COLUMN_ARRAY = 61502`).
- **A re-entrancy bug class in `SurplusRemover`** has been eliminated by removing the mutable
  `$depth` instance property in favour of a stack-local parameter in `doRemove(int $depth)`.
- **Test infrastructure** is cleaner: `assertTotalEquals()` now accepts an optional `$delta`
  for float-mode assertions, eliminating inconsistent assertion patterns in `FloatValueTests`.
- **Cosmetic debt** (`OverflowFixer` double-space, stale comment, header alignment) has been
  cleared.
- **All project-manifest documents** (`api-surface.md`, `constraints.md`, `data-flows.md`,
  `file-tree.md`) were kept in sync throughout.

---

## Work Package Summary

| WP | Title | Pass | Status | Implementation Score |
|----|-------|------|--------|----------------------|
| WP-001 | PHPStan Level 9 Infrastructure | Infrastructure | COMPLETE | 9 / 10 |
| WP-002 | Empty Column Array Guard | Correctness | COMPLETE | 8 / 10 |
| WP-003 | SurplusRemover Recursion Safety Refactor | Correctness | COMPLETE | 9 / 10 |
| WP-004 | `assertTotalEquals()` Delta Parameter | Quality | COMPLETE | 9 / 10 |
| WP-005 | PHPStan Level 9 — Zero Errors | Quality | COMPLETE | 9 / 10 |
| WP-006 | Final Cosmetic Pass | Cleanup | COMPLETE | 10 / 10 |

Average reviewer score: **9.0 / 10**

---

## Metrics

| Metric | Value |
|--------|-------|
| PHPUnit tests passing | 40 / 40 |
| PHPUnit assertions (final) | 98 |
| Test failures | 0 |
| PHPStan errors at level 9 (start) | 8 |
| PHPStan errors at level 9 (end) | **0** |
| Critical blocking issues found | 0 |
| Security issues found | 0 |
| Files modified (source) | `src/Calculator.php`, `src/Calculator/SurplusRemover.php`, `src/Calculator/OverflowFixer.php` |
| Files modified (tests) | `tests/assets/classes/CalculatorTestCase.php`, `tests/testsuites/EdgeCaseTests.php`, `tests/testsuites/FloatValueTests.php`, `tests/testsuites/GetValueTests.php` |
| Files modified (docs) | `docs/config/phpstan.neon`, `composer.json`, `README.md`, `docs/agents/project-manifest/constraints.md`, `docs/agents/project-manifest/api-surface.md`, `docs/agents/project-manifest/data-flows.md` |
| Total pipelines run | 24 (4 pipelines × 6 WPs) |
| Pipeline failures | 0 |

---

## Strategic Recommendations — Gold Nuggets

These items were identified by Reviewer, QA, and Developer agents across multiple work
packages. They are ranked by scope and recurrence.

### High Priority

#### 1. Introduce `getFloatOption()` in `Traits_Optionable` (WP-002, WP-005)

`Traits_Optionable::getOption()` returns `mixed`. Every numeric-option accessor in
`Calculator` (`getMaxTotal()`, `getMinWidth()`) now requires an `is_numeric()` guard before
casting to `(float)` to satisfy PHPStan level 9. This boilerplate will be required in every
future numeric-option accessor. A typed `getFloatOption(string $name): float` helper in the
upstream `application-utils` trait would centralise the narrowing and remove the per-accessor
guards. Until that upstream change is available, the pattern must be applied manually.

#### 2. Fix Secondary `DivisionByZeroError` in `setMinWidth()` (WP-002)

`Calculator::create([])->setMinWidth(5)` still triggers a `DivisionByZeroError` via
`getMaxMinWidth()` (line 141 divides by `$this->operations->countColumns()` without an
empty-array guard). This is a distinct crash path from the one fixed in WP-002. The empty
array check in `getValues()` does not protect `setMinWidth()`. A guard — either at the top
of `setMinWidth()` or inside `getMaxMinWidth()` — is needed.

#### 3. Standardise Exception Types on Public API (WP-002)

`setMinWidth()` throws `\Exception` while `getValues()` now correctly throws
`\InvalidArgumentException`. All user-facing domain validation should use
`\InvalidArgumentException` consistently. This is a minor but real inconsistency in the
public contract.

---

### Medium Priority

#### 4. Add a Code Style Enforcer (WP-002, WP-003, project-level)

A recurring style inconsistency was detected independently in WP-002 and WP-003: the
Developer habitually writes `if (` (space before parenthesis) with K&R opening braces
(same line), while the project convention is `if(` (no space) with Allman braces (next
line). The Reviewer elevated this to a project-level observation. Recommended remediation:

- Add a `.php-cs-fixer.php` (or `.phpcs.xml`) configuration file to the repository root.
- Enforce `no_spaces_after_function_name`, `no_space_after_not_operator`, and
  `braces` (Allman) rules as PHP-CS-Fixer fixers.
- Add `composer cs-fix` and `composer cs-check` entries to `composer.json`.
- Document the style tool in `constraints.md`.

This is non-blocking for any individual WP but will accumulate cosmetic debt in every
future development session.

#### 5. Extract `MAX_RECURSION_DEPTH` Constant in `SurplusRemover` (WP-003)

The depth cap of `100` in `SurplusRemover::doRemove()` is a magic number. Extracting it as
`private const MAX_RECURSION_DEPTH = 100` makes the intent explicit and centralises future
tuning. Both the Developer and Reviewer independently flagged this.

---

### Low Priority

#### 6. PHP File Header Inconsistencies — Remaining Two Files (WP-004, WP-006)

Two source files still violate the mandatory `<?php` / `declare(strict_types=1);` on
consecutive lines (no blank line) constraint:

- `tests/testsuites/GetValueTests.php` — has a blank line between `<?php` and `declare`.
- `src/Calculator/OverflowFixer.php` — uses `declare (strict_types=1);` (space before
  parenthesis) on line 9.

Both are pre-existing and out of scope for the WPs that touched those files. They should be
fixed in the next cosmetic pass (boy scout rule: fix when you touch the file).

#### 7. Fix `minPerCol` Default from `1` to `1.0` (WP-005)

`getDefaultOptions()` sets `'minPerCol' => 1` (integer), but `getMinWidth()` is declared to
return `float`. PHP coerces silently, but the default should be `1.0` to accurately reflect
the float contract and align with the `constraints.md` Option Defaults table.

#### 8. Use Float Literals in `GetValueTests` Data Provider (WP-005)

`provideGetValuesCases()` uses integer literals (e.g. `'one' => 14`) throughout, while
the `@return` PHPDoc declares `array<string, float>`. PHPStan level 9 accepts `int` as
float-compatible, but float literals (`14.0`) would be more honest and reduce confusion for
future maintainers reading the annotation before the data.

#### 9. `analyze-save` CI Safety (WP-001)

`composer.json`'s `analyze-save` script appends `|| true`, which forces an exit code of 0
regardless of PHPStan findings. This script is documented in `README.md` as unsuitable for
CI, but the suppression should also be annotated inline in `composer.json` to prevent
accidental CI misuse. Consider removing `|| true` entirely and relying on redirection only.

---

## Failed / Blocked Items

None. All 24 pipelines passed. No work packages were blocked or failed.

---

## Carry-Forward Work

The following items were explicitly deferred and are ready inputs for the next planning
session:

| Priority | Item | Source | Suggested WP |
|----------|------|---------|--------------|
| High | Fix secondary `DivisionByZeroError` in `setMinWidth()` | WP-002 Reviewer | New WP |
| High | `getFloatOption()` in `Traits_Optionable` (upstream) | WP-002, WP-005 | New WP or upstream PR |
| Medium | Standardise `setMinWidth()` exception type to `\InvalidArgumentException` | WP-002 | New WP |
| Medium | Add PHP-CS-Fixer / PHPCS config + `composer cs-fix` | WP-002, WP-003 | New WP |
| Medium | Extract `SurplusRemover::MAX_RECURSION_DEPTH = 100` | WP-003 | Small WP or cosmetic pass |
| Low | Fix `GetValueTests.php` file header blank line | WP-004, WP-006 | Cosmetic pass |
| Low | Fix `OverflowFixer.php` declare spacing | WP-006 | Cosmetic pass |
| Low | `minPerCol` default `1` → `1.0` | WP-005 | Cosmetic pass |
| Low | `GetValueTests` integer → float literals in data provider | WP-005 | Cosmetic pass |
| Low | `composer.json` `analyze-save`: remove or annotate `|| true` | WP-001 | Cosmetic pass |

---

## Next Steps for the Planner

1. **Open a new plan** addressing the two high-priority carry-forwards (secondary
   `DivisionByZeroError` + `setMinWidth()` exception type) and the medium-priority code
   style enforcer. These three items have the highest compounding risk if left unaddressed.

2. **Bundle the low-priority items** (`minPerCol` default, integer literals,
   `declare` spacing, `analyze-save` annotation) into a single cosmetic WP to be executed
   in less than one session.

3. **Coordinate with the `application-utils` maintainer** regarding `getFloatOption()` in
   `Traits_Optionable`. Until that helper exists, document the required `is_numeric()` guard
   pattern clearly in `constraints.md` (already done in WP-005) so Developer agents follow
   it consistently.

4. **Consider adding the code style check** (`composer cs-check`) to the same pipeline
   sequence that runs `composer analyze` and `vendor/bin/phpunit`, to catch style drift
   automatically in future sessions.
