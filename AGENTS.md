# Agents Guide — column-widths-calculator

> **Operating System for AI Agents.** Read this file completely before touching any source code or tests.

---

## 📚 Project Manifest — Start Here!

**The Project Manifest is the authoritative source of truth.**
If the manifest conflicts with the code, the code is likely wrong — flag it, do not silently fix it.

### 🎯 Location

`docs/agents/project-manifest/`

### 📖 Manifest Documents

| # | File | Purpose |
|---|------|---------|
| 1 | [README.md](docs/agents/project-manifest/README.md) | Project overview, package identity, version, and manifest index |
| 2 | [tech-stack.md](docs/agents/project-manifest/tech-stack.md) | PHP version, Composer setup, architectural patterns, and dependencies |
| 3 | [file-tree.md](docs/agents/project-manifest/file-tree.md) | Annotated directory structure — use this before exploring the filesystem |
| 4 | [api-surface.md](docs/agents/project-manifest/api-surface.md) | Every public constructor, method, and constant with signatures and contracts |
| 5 | [data-flows.md](docs/agents/project-manifest/data-flows.md) | Step-by-step traces of the three main calculation pipelines |
| 6 | [constraints.md](docs/agents/project-manifest/constraints.md) | Non-negotiable rules, gotchas, and naming conventions |

### 📊 Project Identity

| Item | Value |
|------|-------|
| Package | `mistralys/column-widths-calculator` |
| Language | PHP 8.4+ (`declare(strict_types=1)` mandatory in every file) |
| Autoloading | Classmap (not PSR-4) — run `composer dump-autoload` after adding files |
| Architecture | Single public entry point (`Calculator`) + internal Facade/Strategy workers |
| Root namespace | `Mistralys\WidthsCalculator` |
| Internal namespace | `Mistralys\WidthsCalculator\Calculator` |
| Test namespace | `Mistralys\WidthsCalculatorUnitTests` |

---

## 🚀 Quick Start Workflow

Follow this ingestion path exactly. Do **not** open source files before completing steps 1–4.

```
1. Read  docs/agents/project-manifest/README.md        → understand scope & version
      ↓
2. Read  docs/agents/project-manifest/tech-stack.md    → internalize architecture & patterns
      ↓
3. Read  docs/agents/project-manifest/constraints.md   → memorise hard rules before writing a line
      ↓
4. Read  docs/agents/project-manifest/api-surface.md   → know every public signature
      ↓
5. Read  docs/agents/project-manifest/data-flows.md    → understand the three calculation pipelines
      ↓
6. Consult docs/agents/project-manifest/file-tree.md   → locate the exact file(s) you need
      ↓
7. Open source files only as needed for the specific task
```

---

## 📝 Manifest Maintenance Rules

When you make any of the changes below, **you must update the listed manifest files in the same session** before marking the task complete.

| Change Made | Manifest Files to Update |
|-------------|--------------------------|
| Add a new public method to `Calculator` | `api-surface.md` |
| Add a new internal worker class in `src/Calculator/` | `file-tree.md`, `tech-stack.md` (patterns section) |
| Add or rename a source or test file | `file-tree.md` |
| Change a default option value | `constraints.md` (Option Defaults table), `api-surface.md` |
| Add a new configuration option | `api-surface.md`, `constraints.md` |
| Change an exception code or error constant | `api-surface.md`, `constraints.md` |
| Add a new Composer dependency | `tech-stack.md` |
| Change the calculation pipeline order or add a new step | `data-flows.md` |
| Bump the minimum PHP version | `tech-stack.md`, `constraints.md`, `README.md` |
| Add a new test suite | `file-tree.md` |

---

## ⚡ Efficiency Rules — Search Smart

These rules exist to minimise token consumption and avoid redundant filesystem exploration.

- **Locating a file?** Check `file-tree.md` first. Only use a filesystem search if the file is absent from the tree.
- **Understanding a method signature?** Read `api-surface.md` first. Only open the source file if you need the implementation body.
- **Understanding a calculation step?** Read `data-flows.md` first. Only open worker class files if you need to change behaviour.
- **Unsure if something is allowed?** Check `constraints.md` before examining code.
- **Adding a new file?** Confirm the naming convention from `file-tree.md` and the namespace from `constraints.md` before creating it.
- **Never read `vendor/`** for any purpose. All relevant dependency contracts surface through `tech-stack.md` and `api-surface.md`.

---

## 🚨 Failure Protocol & Decision Matrix

| Scenario | Required Action | Priority |
|----------|-----------------|----------|
| Requirement is ambiguous | Apply the most restrictive defensible interpretation and document your assumption as a comment | MUST |
| Manifest contradicts code | Trust the manifest; flag the code discrepancy in a comment and note it in your response; do not silently "fix" the manifest to match bad code | MUST |
| A manifest document lacks a needed entry | Add the missing entry to the manifest before proceeding with implementation | MUST |
| Tempted to instantiate `Calculator` directly | Use `Calculator::create()` — the constructor is private; direct instantiation is a fatal error | MUST |
| Adding or modifying a file without strict types | Add `declare(strict_types=1);` as the second line (after `<?php`) — it is mandatory in every source file | MUST |
| Adding a new source file | Run `composer dump-autoload` to regenerate the classmap; classmap autoloading does not self-update | MUST |
| Modifying column values after `getValues()` has been called | Do not do this — `calculate()` is single-pass and cached; post-calculation mutations corrupt state | MUST NOT |
| Exposing an internal worker class (`src/Calculator/*.php`) in a public API | Do not do this — only `Calculator` is part of the public API | MUST NOT |
| Adding option management code independently | Use `getOption()` / `setOption()` / `getBoolOption()` from `Traits_Optionable` — do not reimplement option storage | MUST NOT |
| `setMinWidth()` throws an exception | Verify `$width ≤ getMaxMinWidth()` (= `getMaxTotal() / columnCount`) before calling it; exception code is `Calculator::ERROR_INVALID_MIN_WIDTH` (61501) | CHECK |
| No test exists for changed behaviour | Write a test in the matching suite under `tests/testsuites/` extending `CalculatorTestCase` | SHOULD |
